from flask import Flask, render_template
from config import Config
from extensions import db, login_manager, bcrypt

app = Flask(__name__)
app.config.from_object(Config)

# Inicializar extensiones
db.init_app(app)
login_manager.init_app(app)
bcrypt.init_app(app)

login_manager.login_view = "auth.login"

# Importar modelos
from models import Usuario

@login_manager.user_loader
def load_user(user_id):
    return Usuario.query.get(int(user_id))

# -----------------------
# RUTA DE INICIO NUEVA
# -----------------------

@app.route("/")
def inicio():
    return render_template("inicio.html")

# -----------------------

# Importar blueprints
from routes import auth, produccion, admin, reportes

app.register_blueprint(auth.bp)
app.register_blueprint(produccion.bp, url_prefix="/produccion")
app.register_blueprint(admin.bp, url_prefix="/admin")
app.register_blueprint(reportes.bp, url_prefix="/reportes")

if __name__ == "__main__":
    with app.app_context():
        db.create_all()

    app.run(debug=True)