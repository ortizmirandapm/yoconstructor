# YoConstructor 

Plataforma web de empleo especializada en el sector de la construcción en Argentina. Conecta empresas del rubro con trabajadores calificados, permitiendo la publicación de ofertas laborales y la postulación según especialidad.

##  Funcionalidades

- Registro diferenciado para **empresas** y **trabajadores**
- Publicación y gestión de ofertas laborales por especialidad
- Sistema de postulaciones con seguimiento de estado
- Panel de administración completo (usuarios, empresas, ofertas, especialidades)
- Sistema de notificaciones automáticas al login
- Filtros por provincia, especialidad y rubro
- Gestión de perfiles con imagen y CV en PDF
- Autenticación segura con `password_hash()` / `password_verify()`

##  Tecnologías

- **Backend:** PHP (sin framework)
- **Base de datos:** MySQL
- **Frontend:** HTML5, TailwindCSS, JavaScript
- **Otros:** AJAX, Git

## Estructura del proyecto
```
YoConstructor/
├── admin-*.php          # Panel de administración
├── index.php            # Home para trabajadores
├── index-empresa.php    # Home para empresas
├── registrarme.php      # Registro de empresas y trabajadores
├── login.php            # Autenticación
├── nueva-oferta.php     # Publicación de ofertas
├── conexion.php         # Configuración de base de datos
├── notificaciones_helper.php
└── sidebar-*.php        # Componentes de navegación
```

##  Instalación local

1. Cloná el repositorio en tu carpeta `htdocs` de XAMPP:
```bash
   git clone https://github.com/ortizmirandapm-hash/yoconstructor.git
```
2. Importá el archivo `.sql` en phpMyAdmin para crear la base de datos
3. Configurá las credenciales en `conexion.php`:
```php
   $conexion = mysqli_connect("localhost", "root", "", "yoconstructor");
```
4. Accedé desde el navegador: `http://localhost/yoconstructor`

## 👤 Autor

**Pablo Martín Ortiz Miranda**  
Técnico Superior en Desarrollo de Software  
 Catamarca, Argentina  
 [LinkedIn](https://linkedin.com/in/martinomiranda)  
 [GitHub](https://github.com/ortizmirandapm-hash)

---

> Proyecto desarrollado como trabajo integrador de la Tecnicatura Superior en Desarrollo de Software — Instituto Superior General San Martín, Catamarca (2026).