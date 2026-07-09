<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Regla de acceso Moodle Proctor';
$string['privacy:metadata'] = 'La regla de acceso no almacena datos personales. Los datos los guarda local_moodle_proctor.';

$string['proctoringrequired'] = 'Habilitar supervisión (proctoring)';
$string['proctoringrequired_help'] = 'Requiere Moodle Proctor (local_moodle_proctor) instalado y configurado. Captura imágenes de webcam durante el intento y registra incidencias.';
$string['proctoringenabled'] = 'La supervisión está habilitada para este cuestionario';
$string['requirewebcam'] = 'Requerir webcam durante el intento';
$string['requireextension'] = 'Requerir extensión de navegador durante el intento';
$string['needenrollment'] = 'Debe completar el enrollment de proctoring antes de este cuestionario. <a href="{$a}">Completar enrollment</a>';
$string['needenrollment_short'] = 'Debe completar el enrollment de proctoring antes de este cuestionario.';
$string['preflight_consent'] = 'Consiento la supervisión por webcam durante este intento';
$string['preflight_consentrequired'] = 'Debe aceptar el consentimiento de supervisión para continuar';

$string['cameralost'] = 'Se perdió el acceso a la webcam. Vuelva a habilitar la cámara.';
$string['focuslost'] = 'La ventana del examen perdió el foco. Mantenga esta pestaña activa durante el intento.';
$string['uploadfailed'] = 'Error al subir la captura de supervisión. Si persiste, contacte al docente.';
$string['cameradenied'] = 'Se requiere permiso de cámara para este cuestionario supervisado.';
$string['cameranotsupported'] = 'Su navegador no admite acceso a la webcam.';
$string['tabhidden'] = 'La pestaña del examen quedó oculta. Esta incidencia fue registrada.';
$string['identity_verifying'] = 'Verificando su identidad con la cédula registrada…';
$string['identity_verified'] = 'Identidad verificada correctamente.';
$string['identity_retry'] = 'Reintentar';
$string['identity_leave'] = 'Salir del cuestionario';
$string['settings_info_heading'] = 'Configuración';
$string['settings_info_desc'] = 'La configuración principal de supervisión, almacenamiento, RUT y verificación de identidad está en <a href="/admin/settings.php?section=local_moodle_proctor_settings">Plugins locales → Moodle Proctor</a>.';
