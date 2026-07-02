# Moodle Proctor — Quiz Access Rule

[![Moodle](https://img.shields.io/badge/Moodle-5.2%2B-orange?style=flat-square&logo=moodle)](https://moodle.org)
[![PHP](https://img.shields.io/badge/PHP-8.5%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL--3.0-blue?style=flat-square)](LICENSE)
[![Branch](https://img.shields.io/badge/Branch-MOODLE__52-green?style=flat-square)](https://github.com/Johnrivera7/moodle_quizaccess_proctor/tree/MOODLE_52)
[![Depends on](https://img.shields.io/badge/Requires-local__moodle__proctor-red?style=flat-square)](https://github.com/Johnrivera7/moodle_proctor)
[![Version](https://img.shields.io/badge/Version-1.6.0-informational?style=flat-square)](version.php)

Regla de acceso a cuestionarios: webcam, capturas periódicas, **verificación de identidad al entrar** (Amazon Rekognition: cédula enrollment vs. rostro en vivo), **alertas en vivo** al estudiante (rostros, pestaña, copiar) e integración con verificación de identidad del núcleo.

| | |
|---|---|
| **Autor** | [John Rivera](https://github.com/Johnrivera7) |
| **Contacto** | [johnriveragonzalez7@gmail.com](mailto:johnriveragonzalez7@gmail.com) |
| **Componente** | `quizaccess_moodle_proctor` |
| **Instalación** | `mod/quiz/accessrule/moodle_proctor/` |
| **Repositorio** | https://github.com/Johnrivera7/moodle_quizaccess_proctor |
| **Rama** | **`MOODLE_52`** — Moodle 5.2+ / PHP 8.5+ |

> Moodle 4.5: rama [`MOODLE_45`](https://github.com/Johnrivera7/moodle_quizaccess_proctor/tree/MOODLE_45)

---

## Requisitos

| Requisito | Versión |
|-----------|---------|
| Moodle | **5.2+** (`2026042000`) |
| PHP | **8.5+** |
| Plugin núcleo | [`local_moodle_proctor`](https://github.com/Johnrivera7/moodle_proctor/tree/MOODLE_52) **v1.6.0+** |

---

## Instalación

```bash
cd /path/to/moodle/mod/quiz/accessrule
git clone -b MOODLE_52 https://github.com/Johnrivera7/moodle_quizaccess_proctor.git moodle_proctor
```

**Administración → Notificaciones** (después de instalar `local_moodle_proctor`).

---

## Configuración por cuestionario

**Editar cuestionario → Restricciones extra en los intentos**

| Opción | Descripción |
|--------|-------------|
| **Enable proctoring** | Activa supervisión |
| **Require webcam** | Cámara activa durante intento |
| **Require browser extension** | Exige extensión Chromium (opcional, desactivada por defecto en el núcleo) |

El estudiante debe haber completado la **verificación de identidad** (cédula + rostro) antes del intento si el enrollment está habilitado en el núcleo.

### Verificación de identidad al entrar (v1.6)

Si en el núcleo están activos **Verificar identidad al entrar al cuestionario** y **Amazon Rekognition**:

1. Al iniciar el intento, `proctor.js` activa la webcam.
2. Captura el rostro y llama `local_moodle_proctor_verify_quiz_identity`.
3. El servidor compara con la **foto de la cédula** del enrollment (`id_card.jpg`).
4. Si no coincide: registra incidencia `identity_mismatch`, guarda fotos y similitud en `face_report.php`.
5. Si **Bloquear intento si identidad no coincide** está activo, muestra overlay y no permite continuar.

Durante el intento, `proctor.js` también muestra **banners de alerta** con miniatura y cajas faciales cuando el análisis Python o el navegador detectan incidencias.

Ajustes del plugin: **Administración → Cuestionarios → Moodle Proctor quiz access rule**.

---

## Suite MOODLE_52

| Repo | Rol |
|------|-----|
| [moodle_proctor](https://github.com/Johnrivera7/moodle_proctor/tree/MOODLE_52) | Núcleo |
| **moodle_quizaccess_proctor** | ← Este plugin |
| [moodle_block_proctor](https://github.com/Johnrivera7/moodle_block_proctor/tree/MOODLE_52) | Bloque |
| [moodle_proctor_python](https://github.com/Johnrivera7/moodle_proctor_python/tree/MOODLE_52) | Python |
| [moodle_proctor_extension](https://github.com/Johnrivera7/moodle_proctor_extension/tree/MOODLE_52) | Extensión |

[Guía de pruebas](https://github.com/Johnrivera7/moodle_proctor/blob/MOODLE_52/TESTING.md)

---

## Licencia

GPL-3.0+ — [LICENSE](LICENSE)

## Soporte

- **Autor:** [John Rivera](https://github.com/Johnrivera7) · [johnriveragonzalez7@gmail.com](mailto:johnriveragonzalez7@gmail.com)
- **Issues:** https://github.com/Johnrivera7/moodle_quizaccess_proctor/issues
