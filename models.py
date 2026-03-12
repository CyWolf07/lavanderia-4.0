from extensions import db
from flask_login import UserMixin
from email_validator import validate_email, EmailNotValidError
from datetime import date

class Rol(db.Model):
    __tablename__ = "roles"

    id = db.Column(db.Integer, primary_key=True)
    nombre = db.Column(db.String(50), nullable=False, unique=True)
    permisos = db.Column(db.Text)

    usuarios = db.relationship("Usuario", backref="rol", lazy=True)


class Usuario(UserMixin, db.Model):
    __tablename__ = "usuarios"

    id = db.Column(db.Integer, primary_key=True)
    nombre = db.Column(db.String(100), nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password = db.Column(db.String(200), nullable=False)
    rol_id = db.Column(db.Integer, db.ForeignKey("roles.id"), nullable=False)

    producciones = db.relationship("Produccion", backref="usuario", lazy=True)

    def validar_email(self):
        try:
            validate_email(self.email)
            return True
        except EmailNotValidError:
            return False


class Prenda(db.Model):
    __tablename__ = "prendas"

    id = db.Column(db.Integer, primary_key=True)
    nombre = db.Column(db.String(100), nullable=False)
    precio_unitario = db.Column(db.Numeric(10,2), nullable=False)

    producciones = db.relationship("Produccion", backref="prenda", lazy=True)


class Produccion(db.Model):
    __tablename__ = "producciones"

    id = db.Column(db.Integer, primary_key=True)
    usuario_id = db.Column(db.Integer, db.ForeignKey("usuarios.id"), nullable=False)
    prenda_id = db.Column(db.Integer, db.ForeignKey("prendas.id"), nullable=False)
    cantidad = db.Column(db.Integer, nullable=False)
    fecha_registro = db.Column(db.Date, default=date.today, nullable=False)