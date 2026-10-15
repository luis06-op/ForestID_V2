# Diagrama de Flujo de Navegación del Sistema — ForestID

Flujo integral de usuarios públicos, operarios de campo y administradores desde el punto de entrada `index.php` hasta los paneles correspondientes y la interacción con códigos QR.

```mermaid
flowchart TD
    Start([Usuario ingresa a index.php]) --> CheckAuth{¿Existe Sesión Activa?}

    %% Flujo Sin Sesión
    CheckAuth -- No --> Landing[Landing Pública: publico/index.php]
    
    Landing --> ActionPublic{Acción Ciudadana}
    ActionPublic -- "Escanear QR Físico con Celular" --> FichaPub[Ficha Técnica: publico/ficha.php?codigo=XXX]
    ActionPublic -- "Búsqueda / Catálogo" --> BuscarPub[Buscador Público: publico/buscar.php]
    ActionPublic -- "Iniciar Sesión" --> Login[Login: auth/login.php]
    
    FichaPub --> ReportarNovedad[Reportar Novedad Ciudadana: publico/reportar_novedad.php]
    ReportarNovedad --> FichaPub

    BuscarPub --> FichaPub

    %% Flujo Login y Recuperación
    Login --> AuthCheck{Validar Credenciales y CSRF}
    AuthCheck -- "Error" --> Login
    AuthCheck -- "Olvidó Contraseña" --> Recuperar[auth/recuperar_password.php]
    Recuperar --> Restablecer[auth/restablecer_password.php]
    Restablecer --> Login

    %% Flujo Con Sesión
    AuthCheck -- "Éxito" --> CheckAuth
    CheckAuth -- Sí --> RouterRoles{Evaluar Rol de Usuario}

    %% Rol Administrador
    RouterRoles -- "AdminEmincut" --> AdminDash[Dashboard Admin: admin/dashboard.php]
    AdminDash --> AdminIndiv[Individuos: admin/individuos/]
    AdminDash --> AdminZonas[Zonas: admin/zonas/]
    AdminDash --> AdminInst[Instituciones: admin/instituciones/]
    AdminDash --> AdminUsers[Usuarios: admin/usuarios/]
    AdminDash --> AdminQR[Gestor e Impresión QR: admin/qr/]
    AdminDash --> AdminRep[Reportes y CSV: admin/reportes/]
    AdminDash --> AdminLogs[Auditoría: admin/logs/]

    %% Rol Operario
    RouterRoles -- "OperarioEmincut" --> OperarioDash[Dashboard Operario: operario/dashboard.php]
    OperarioDash --> ScanCamera[Escáner QR Cámara: operario/escanear.php]
    OperarioDash --> AltaGPS[Alta Rápida Campo con GPS: operario/registrar_individuo.php]
    OperarioDash --> OperarioIndiv[Individuos de Zona: operario/individuos/]
    OperarioDash --> OperarioMant[Registrar Labor: operario/actividades/crear.php]

    ScanCamera -- "QR Detectado" --> OperarioDetalle[Detalle Inspección: operario/individuos/detalle.php]
    OperarioIndiv --> OperarioDetalle

    %% Cierre de sesión
    AdminDash --> Logout[auth/logout.php]
    OperarioDash --> Logout
    Logout --> Landing
```
