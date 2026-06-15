# Moodle Proctor — Quiz Access Rule

[![Moodle](https://img.shields.io/badge/Moodle-4.5%2B-orange?style=flat-square&logo=moodle)](https://moodle.org)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL--3.0-blue?style=flat-square)](LICENSE)
[![Depends on](https://img.shields.io/badge/Requires-local__moodle__proctor-red?style=flat-square)](https://github.com/Johnrivera7/moodle_proctor)
[![Version](https://img.shields.io/badge/Version-1.0.0-informational?style=flat-square)](version.php)

Regla de acceso a cuestionarios: webcam, preflight de consentimiento e integración con el núcleo de supervisión.

| | |
|---|---|
| **Componente** | `quizaccess_moodle_proctor` |
| **Instalación** | `mod/quiz/accessrule/moodle_proctor/` |
| **Repositorio** | https://github.com/Johnrivera7/moodle_quizaccess_proctor |
| **Autor** | [John Rivera](https://github.com/Johnrivera7) |

---

## Requisitos

| Requisito | Versión |
|-----------|---------|
| Moodle | 4.5+ |
| PHP | 8.1+ |
| Plugin núcleo | [`local_moodle_proctor`](https://github.com/Johnrivera7/moodle_proctor) |

---

## Instalación

```bash
cd /path/to/moodle/mod/quiz/accessrule
git clone -b MOODLE_45 https://github.com/Johnrivera7/moodle_quizaccess_proctor.git moodle_proctor
```

Luego **Administración → Notificaciones**.

> ⚠️ Instalar **después** de `local_moodle_proctor`.

---

## Configuración por cuestionario

**Editar cuestionario → Restricciones extra en los intentos**

| Opción | Descripción |
|--------|-------------|
| **Enable proctoring** | Activa supervisión en este quiz |
| **Require webcam** | Exige cámara activa durante el intento |
| **Require browser extension** | Exige extensión Chromium (si está habilitada a nivel sitio) |

---

## Comportamiento

| Fase | Qué ocurre |
|------|------------|
| **Acceso** | Bloquea si falta enrollment de proctoring |
| **Preflight** | Consentimiento obligatorio antes del intento |
| **Intento** | Miniatura webcam + capturas cada N segundos |
| **Monitoreo** | Log de pestaña oculta, foco, copiar/pegar |

---

## Suite completa

| Repo | Rol |
|------|-----|
| [moodle_proctor](https://github.com/Johnrivera7/moodle_proctor) | Núcleo, SFS, informes |
| **moodle_quizaccess_proctor** | ← Este plugin |
| [moodle_block_proctor](https://github.com/Johnrivera7/moodle_block_proctor) | Bloque enrollment |
| [moodle_proctor_python](https://github.com/Johnrivera7/moodle_proctor_python) | Análisis facial |
| [moodle_proctor_extension](https://github.com/Johnrivera7/moodle_proctor_extension) | Browser lock |

Guía de pruebas: [TESTING.md](https://github.com/Johnrivera7/moodle_proctor/blob/MOODLE_45/TESTING.md)

---

## Licencia

GPL-3.0+ — [LICENSE](LICENSE)
