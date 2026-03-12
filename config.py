import os

class Config:
    SECRET_KEY = "clave_secreta_lavanderia"

    SQLALCHEMY_DATABASE_URI = "mysql+pymysql://root:root@localhost/lavanderia"

    SQLALCHEMY_TRACK_MODIFICATIONS = False
