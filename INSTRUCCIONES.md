# Guía de Instalación en Hostinger

## 1. Importar Base de Datos
1. Entra a tu **Panel de Hostinger** -> **Base de Datos**.
2. Verifica que tengas creada la base de datos `u471794305_pedidos_ohlala` (si no, créala).
3. Entra a **phpMyAdmin**.
4. Selecciona tu base de datos a la izquierda.
5. Ve a la pestaña **Importar**.
6. Sube el archivo `.sql` correspondiente.
7. Dale clic a "Continuar" para importar todas las tablas y datos.

## 2. Subir Archivos
1. Entra al **Administrador de Archivos** de Hostinger.
2. Ve a `public_html`.
3. Sube **TODOS** los archivos de este repositorio.
4. **IMPORTANTE:** El archivo `conexion.php` debe estar configurado con tus credenciales de base de datos.
5. No compartas tus credenciales en el repositorio.

## 3. Probar
- Entra a tu dominio configurado.
- Intenta iniciar sesión con tus credenciales de administrador.
- Si entras al Dashboard, ¡felicidades! Todo está funcionando.

## Notas
- Si tienes problemas de "Acceso denegado", verifica que el usuario de base de datos tenga ASIGNADA la base de datos con todos los permisos.
