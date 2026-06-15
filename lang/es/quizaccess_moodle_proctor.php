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
$string['preflight_consent'] = 'Consiento la supervisión por webcam durante este intento';
$string['preflight_consentrequired'] = 'Debe aceptar el consentimiento de supervisión para continuar';

$string['cameralost'] = 'Se perdió el acceso a la webcam. Vuelva a habilitar la cámara.';
$string['tabhidden'] = 'La pestaña del examen quedó oculta. Esta incidencia fue registrada.';
