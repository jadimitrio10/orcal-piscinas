# OrCal S.A.S — sitio web

Clon estático de https://orcalpiscinas.com (WordPress + tema Construction) convertido a HTML/CSS/JS plano, alojado en CaribeHost (Plesk).

## Estructura

- `index.html` — Inicio
- `empresa/`, `servicios/`, `proyectos/`, `contactenos/` — secciones del menú
- `servicios/{diseno-proyectos,piscinas,fuentes,tratamiento-aguas}/`, `parques-acuaticos/`, `mantenimiento/` — subpáginas de servicios
- `project/*/` — fichas de cada proyecto
- `assets/` — galería móvil (`orcal-galeria.css/js`)
- `wp-content/` y `wp-includes/` — estilos, scripts, fuentes e imágenes originales (rutas conservadas para no romper referencias)

## Publicación

Cada push a `main` sube el sitio a CaribeHost por FTPS (`.github/workflows/publicar-caribehost.yml`, secretos `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_DIR`). `verificar-caribehost.yml` y `probar-formulario.yml` revisan el sitio y el formulario.

## Formulario de contacto

`contactenos/` envía por POST a `contacto.php`, que manda el correo por SMTP (mx.caribehost.email:465, cuenta webmaster@orcal.com.co) a orcalpiscinas@orcal.com.co. La clave SMTP vive solo en el servidor, en `contacto-config.php` (fuera de git):

```php
<?php return array('host' => 'mx.caribehost.email', 'puerto' => 465, 'usuario' => 'webmaster@orcal.com.co', 'clave' => '...');
```

## Desarrollo local

Cualquier servidor estático sirve para las páginas (el formulario necesita PHP):

```bash
php -S localhost:8000
```
