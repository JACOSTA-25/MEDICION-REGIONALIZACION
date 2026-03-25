# Analisis de PDF: MEDICION vs Inventario-Uniguajira-Laravel12

## Alcance revisado

Se revisaron los generadores y plantillas que hoy existen en:

- `MEDICION/reporteProceso.php`
- `MEDICION/reporte_individual.php`
- `MEDICION/templates/*.html`
- `Inventario-Uniguajira-Laravel12/app/Http/Controllers/ReportController.php`
- `Inventario-Uniguajira-Laravel12/app/Services/Reports/SimplePdfService.php`
- `Inventario-Uniguajira-Laravel12/resources/views/reports/pdf/*.blade.php`

Este documento solo deja el analisis listo. No aplica todavia la migracion del flujo PDF al proyecto `Medicion-De-Servicios-Uniguajira`.

## Como genera PDF el aplicativo MEDICION

### Flujo actual

1. Recibe parametros por `GET` (`id_proceso`, `id_dependencia`, `desde`, `hasta`).
2. Convierte fechas manualmente desde `DD/MM/AAAA`.
3. Consulta la base de datos con SQL directo y `mysqli`.
4. Calcula indicadores por pregunta y consolidados con funciones en el mismo archivo.
5. Genera graficas con `QuickChart`.
6. Carga plantillas HTML estaticas desde `templates/`.
7. Reemplaza marcadores como `{{DEPENDENCIA}}`, `{{FILAS_TABLA}}`, `{{IMG_PREGUNTA}}`.
8. Concatena todas las paginas en un solo HTML largo.
9. Renderiza el PDF con `Dompdf` directamente en el mismo script.

### Estructura detectada del PDF legado

- Portada institucional con imagen de fondo y placeholders del proceso o dependencia.
- Pagina de objetivo y numero de usuarios.
- Pagina de distribuciones por estamento, programa y servicio.
- Seis paginas intermedias, una por cada pregunta de satisfaccion.
- Pagina de consolidado global con tabla de indicadores y grafica general.
- Pagina final de conclusiones narrativas.

### Debilidades tecnicas del flujo legado

- El controlador, la consulta, la estadistica, la composicion HTML y la descarga viven en el mismo archivo.
- Usa SQL crudo y mezcla reglas de negocio con presentacion.
- Depende de rutas absolutas tipo `http://localhost/...` para imagenes.
- Duplica contenido dentro de varias plantillas (`pag4.html`, `pag10.html`, `pag11.html`).
- No hay capa reutilizable para construir PDFs.
- La validacion de parametros es manual y parcial.
- El mantenimiento por modulo es costoso porque cada reporte crece como script monolitico.

## Como genera PDF Inventario-Uniguajira-Laravel12

### Flujo actual

1. El formulario envia un payload corto con tipo de reporte y filtros.
2. `ReportController` valida los datos segun el tipo de reporte.
3. El controlador construye un `payload` tipado: vista Blade, datos, papel y orientacion.
4. La informacion se prepara en metodos dedicados (`inventoryData`, `groupData`, `allInventoriesData`, etc.).
5. Una vista Blade especializada arma el HTML final.
6. `SimplePdfService` encapsula la configuracion de `Dompdf`.
7. El binario PDF se guarda en `storage` y luego se descarga cuando el usuario lo solicita.

### Fortalezas tecnicas del flujo Inventario

- Separacion clara entre validacion, consulta, plantilla y generacion de PDF.
- Plantillas Blade por tipo de reporte en vez de placeholders manuales.
- Servicio reutilizable para `Dompdf`.
- Uso de `Storage` para persistir y descargar.
- Nombres de archivo sanitizados.
- Menor acoplamiento con rutas locales o `localhost`.

## Diferencias clave que se deben migrar despues

### Lo que debe conservarse de MEDICION

- La narrativa del informe institucional.
- La secuencia logica del documento: objetivo, caracterizacion, preguntas, consolidado y conclusiones.
- Los indicadores de satisfaccion por cada pregunta.
- El enfoque por proceso y por dependencia.

### Lo que debe reemplazarse por el estilo Inventario

- Los scripts PHP monoliticos.
- El reemplazo manual de placeholders en archivos HTML.
- La construccion del PDF en un solo archivo.
- La descarga inmediata sin almacenamiento del reporte.

### Objetivo tecnico recomendado para la migracion

- Crear un `ReportController` para medicion.
- Crear un servicio dedicado, por ejemplo `SurveyPdfService`.
- Migrar cada pagina del PDF legado a vistas Blade parciales.
- Mantener `Dompdf`, pero configurado desde una sola clase de servicio.
- Preparar un `payload` por tipo de reporte (`general`, `proceso`, `individual`).
- Reemplazar las rutas absolutas de imagenes por `data URI` o `public_path`.
- Separar la generacion de indicadores del render HTML.

## Mapa sugerido para el proyecto Medicion-De-Servicios-Uniguajira

### Reporte general

- Consolidado por proceso.
- Filtros: proceso, dependencia opcional, rango de fechas.
- Salida futura: resumen general, tablas y tendencia consolidada.

### Reporte por proceso

- Analisis por proceso con sus dependencias.
- Filtros: proceso, dependencia opcional, rango de fechas.
- Salida futura: indicadores por pregunta, distribucion por estamento y servicios.

### Reporte individual

- Analisis enfocado en una dependencia concreta.
- Filtros: proceso, dependencia obligatoria, rango de fechas.
- Salida futura: narrativa y conclusiones puntuales por dependencia.

## Riesgos a controlar al momento de aplicar la migracion

- Evitar romper el esquema legado de tablas (`proceso`, `dependencia`, `servicio`, `respuesta`).
- Validar que los porcentajes no dividan por cero.
- Estandarizar el rango de fechas a `Y-m-d`.
- Revisar si las graficas de `QuickChart` seguiran externas o si deben renderizarse de otro modo.
- Confirmar si los reportes se deben descargar al instante o almacenar como en Inventario.

## Estado actual en este proyecto

En esta fase solo quedaron listos:

- Los formularios de seleccion en los modulos.
- El acceso a la encuesta por enlace firmado.
- La base visual para futuros filtros de PDF.

La generacion real de PDFs todavia no se conecto. Ese trabajo queda pendiente para una segunda fase usando este analisis.
