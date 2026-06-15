# Moodle Proctor — Quiz access rule (`quizaccess_moodle_proctor`)

Regla de acceso a cuestionarios para supervisión con webcam. Parte de la suite [Moodle Proctor](https://github.com/Johnrivera7/moodle_proctor).

- **Repositorio:** https://github.com/Johnrivera7/moodle_quizaccess_proctor
- **Rama:** `MOODLE_45`
- **Instalación:** `mod/quiz/accessrule/moodle_proctor/`
- **Depende de:** `local_moodle_proctor`

## Instalación

```bash
cd /path/to/moodle/mod/quiz/accessrule
git clone -b MOODLE_45 https://github.com/Johnrivera7/moodle_quizaccess_proctor.git moodle_proctor
```

Luego **Notificaciones** en Moodle.

## Configuración por cuestionario

**Editar cuestionario → Restricciones extra en los intentos:**

- **Enable proctoring** — activa supervisión
- **Require webcam** — exige cámara durante el intento
- **Require browser extension** — exige extensión (si está habilitada a nivel sitio)

## Preflight

Antes del intento el estudiante debe aceptar el consentimiento de supervisión.

## Durante el intento

- Miniatura de webcam flotante
- Capturas periódicas vía webservice `local_moodle_proctor_upload_snapshot`
- Log de incidencias (pestaña, foco, copiar/pegar)

## Licencia

GPL-3.0+ — ver [LICENSE](LICENSE).
