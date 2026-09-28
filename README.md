# OrCal S.A.S — sitio web

Clon estático de https://www.orcalpiscinas.com (WordPress + tema Construction) convertido a HTML/CSS/JS plano para desplegar en Netlify.

## Estructura

- `index.html` — Inicio
- `empresa/`, `servicios/`, `proyectos/`, `contactenos/` — secciones del menú
- `servicios/{diseno-proyectos,piscinas,fuentes,tratamiento-aguas}/`, `parques-acuaticos/`, `mantenimiento/` — subpáginas de servicios
- `project/*/` — fichas de cada proyecto
- `wp-content/` y `wp-includes/` — estilos, scripts, fuentes e imágenes originales (rutas conservadas para no romper referencias)

## Formulario de contacto

El formulario de `contactenos/` usa Netlify Forms (`data-netlify="true"`, formulario `contacto`). Los envíos aparecen en el panel de Netlify > Forms.

## Desarrollo local

Cualquier servidor estático sirve, por ejemplo:

```bash
npx serve .
```
