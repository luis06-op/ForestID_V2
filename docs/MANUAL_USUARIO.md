# Manual de Usuario — ForestID (Emincut)

Guía completa de uso del sistema **ForestID** para los tres roles de usuario: **Usuario Público / Ciudadano**, **Operario de Campo (OperarioEmincut)** y **Administrador General (AdminEmincut)**.

---

## 1. Módulo Público y Ciudadano (Sin Login)

El acceso público está diseñado con enfoque **Mobile-First** para permitir a cualquier persona consultar la información del árbol al escanear la placa QR física con su celular.

### 1.1 Escaneo de la Placa QR en Campo
1. Abra la aplicación de cámara de su teléfono inteligente (Android o iOS).
2. Apunte al código QR grabado en la placa del árbol.
3. Pulse la notificación emergente que abrirá automáticamente la URL:
   `https://[dominio]/publico/ficha.php?codigo=EBG-PARQ-001`
4. Se desplegará la **Ficha Técnica Oficial del Individuo Forestal**:
   - Nombre común y científico.
   - Fotografía del espécimen y familia botánica.
   - **Badge de Salud Fitosanitaria** (Verde: Sano, Ámbar: En observación, Rojo: En riesgo).
   - Mediciones dasométricas: DAP (Diámetro a la altura del pecho), Altura total estimada y Diámetro de copa.
   - Mapa de ubicación geográfica interactivo con Leaflet.
   - Línea de tiempo de intervenciones recientes (podas, fertilización, riego).

### 1.2 Reportar Novedad o Riesgo
Cualquier ciudadano puede reportar afectaciones sobre un árbol:
1. En la ficha técnica del árbol, pulse el botón naranja **"Reportar Novedad sobre este Árbol"**.
2. Seleccione el tipo de afectación (ej. *Rama caída*, *Comején o plaga*, *Interferencia con cables*, *Riesgo de volcamiento*).
3. Escriba una descripción de lo observado.
4. Opcionalmente, adjunte una fotografía tomada con la cámara del celular.
5. Pulse **Enviar Reporte**. La novedad ingresará a la bitácora técnica de Emincut.

### 1.3 Explorador y Buscador de Árboles (`publico/buscar.php`)
- Permite buscar árboles por texto libre, filtrar por sector/zona de El Bagre, filtrar por estado de salud o por tipo de espécimen (Árbol, Palma, Arbusto, Planta ornamental).

---

## 2. Módulo Operario de Campo (OperarioEmincut)

Destinado al personal técnico encargado de inspecciones, podas y censos silviculturales.

### 2.1 Inicio de Sesión
1. Ingrese a `auth/login.php`.
2. Escriba su correo de funcionario (ej. `operario@emincut.com`) y su contraseña.
3. El sistema lo redirigirá directamente a su **Dashboard Operativo**.

### 2.2 Escáner de Placa QR con Cámara (`operario/escanear.php`)
1. Pulse en el menú **"Escanear Placa QR"** o en el botón de cámara del Dashboard.
2. Conceda permiso de acceso a la cámara en el navegador.
3. Enfoque el código QR de la placa física.
4. El sistema detectará la placa inmediatamente y abrirá la pantalla de gestión del espécimen.

### 2.3 Registro de Mantenimiento e Intervención
1. En la ficha del árbol, pulse **"Registrar Trabajo"**.
2. Seleccione la labor: Poda, Fumigación, Fertilización, Riego, Tutoreo, Trasplante o Tala.
3. Detalle las herramientas, insumos aplicados y ramas intervenidas.
4. Tome una foto como evidencia del antes/después.
5. Guarde el registro. Quedará grabado en la historia vitalicia del árbol.

### 2.4 Actualización del Estado Fitosanitario
1. Pulse **"Actualizar Salud"**.
2. Seleccione la nueva condición fitosanitaria según la escala técnica.
3. Escriba el diagnóstico y las recomendaciones para el próximo ciclo.

### 2.5 Alta Rápida en Campo con GPS (`operario/registrar_individuo.php`)
1. Pulse **"Alta Rápida en Campo"**.
2. Pulse el botón **"Capturar Mi GPS"** para fijar las coordenadas exactas de latitud y longitud.
3. Digite el nombre común y datos dasométricos preliminares (DAP, altura, copa).
4. Tome la fotografía principal del árbol con la cámara.
5. Al guardar, el sistema generará automáticamente el código de placa y el archivo de código QR.

---

## 3. Módulo Administrador General (AdminEmincut)

Destinado a la gerencia técnica y directiva de Emincut.

### 3.1 Dashboard de Indicadores y Gráficos
- Visualización en tiempo real del censo total, especímenes sanos vs en observación/riesgo, y mantenimientos del mes.
- Gráficos interactivos Chart.js con la distribución por estado de salud y densidad por zonas territoriales de El Bagre.
- Alertas inmediatas de árboles en riesgo que requieren intervención prioritaria.

### 3.2 Gestión de Individuos Forestales (`admin/individuos/`)
- Censo completo con filtros avanzados y buscador multifactorial.
- Creación detallada de árboles con generación automática de placa QR física en formato vectorial SVG.
- Ficha técnica administrativa con historial de auditoría y bajas lógicas.

### 3.3 Gestor e Impresión de Placas Físicas QR (`admin/qr/`)
- Muestra la galería de códigos QR vectoriales generados y el estado de los archivos faltantes.
- Permite **regenerar un QR individual o todo el inventario** con la URL canónica configurada en `APP_URL`.
- Permite **imprimir individualmente o en lote** las placas listas con formato de corte y perforación para instalar físicamente en los árboles de cada zona de El Bagre.

### 3.4 Reportes y Exportación a CSV / Excel (`admin/reportes/`)
- Tablero de métricas dasométricas promedio (DAP promedio, Altura promedio, Diámetro de copa).
- Botón **"Exportar Datos a CSV"** para descargar todo el censo estructurado y analizarlo en Microsoft Excel o software GIS.

### 3.5 Gestión de Zonas y Usuarios
- Alta y edición de sectores y zonas internas de Emincut en El Bagre.
- Control de usuarios: creación de cuentas, asignación de roles (`AdminEmincut`, `OperarioEmincut`, `UsuarioPublico`), reseteo de contraseñas y activación/desactivación.

### 3.6 Auditoría y Bitácora de Logs (`admin/logs/`)
- Registro inmutable de cada acción sensible realizada en el sistema (inicios de sesión, altas, ediciones, bajas lógicas, evaluaciones y reportes) con fecha, hora, usuario e IP de origen.
