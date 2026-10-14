# Moodle Proctor — Quiz Access Rule

[![Moodle](https://img.shields.io/badge/Moodle-5.3-orange?style=flat-square&logo=moodle)](https://moodle.org)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL--3.0-blue?style=flat-square)](LICENSE)
[![Branch](https://img.shields.io/badge/Branch-MOODLE__53-green?style=flat-square)](https://github.com/Johnrivera7/moodle_quizaccess_proctor/tree/MOODLE_53)
[![Depends on](https://img.shields.io/badge/Requires-local__moodle__proctor-red?style=flat-square)](https://github.com/Johnrivera7/moodle_proctor)
[![Version](https://img.shields.io/badge/Version-1.6.4-informational?style=flat-square)](version.php)

Regla de acceso a cuestionarios: webcam, capturas periódicas, **verificación de identidad al entrar y cada 90 segundos** (OpenCV local o Amazon Rekognition), **bloqueo del navegador** (pantalla completa, monitor compartido, copiar/pegar y atajos) y alertas en vivo.

| | |
|---|---|
| **Autor** | [John Rivera](https://github.com/Johnrivera7) |
| **Contacto** | [johnriveragonzalez7@gmail.com](mailto:johnriveragonzalez7@gmail.com) |
| **Componente** | `quizaccess_moodle_proctor` |
| **Instalación** | `mod/quiz/accessrule/moodle_proctor/` |
| **Repositorio** | https://github.com/Johnrivera7/moodle_quizaccess_proctor |
| **Rama** | **`MOODLE_53`** — Moodle 5.3 / PHP 8.3+ |

> Moodle 5.2: rama [`MOODLE_52`](https://github.com/Johnrivera7/moodle_quizaccess_proctor/tree/MOODLE_52). Moodle 4.5: rama [`MOODLE_45`](https://github.com/Johnrivera7/moodle_quizaccess_proctor/tree/MOODLE_45).

---

## Requisitos

| Requisito | Versión |
|-----------|---------|
| Moodle | **5.3** (`2026100500`) |
| PHP | **8.3+** |
| Plugin núcleo | [`local_moodle_proctor`](https://github.com/Johnrivera7/moodle_proctor/tree/MOODLE_53) **v1.6.4** |

---

## Instalación

```bash
cd /path/to/moodle/mod/quiz/accessrule
git clone -b MOODLE_53 https://github.com/Johnrivera7/moodle_quizaccess_proctor.git moodle_proctor
```

En Moodle 5.3 la carpeta pública es `public/`. El comando anterior se ejecuta dentro de `public/mod/quiz/accessrule` si el sitio usa esa estructura.

**Administración → Notificaciones** (después de instalar `local_moodle_proctor`).

---

## Configuración por cuestionario

**Editar cuestionario → Restricciones extra en los intentos**

| Opción | Descripción |
|--------|-------------|
| **Enable proctoring** | Activa supervisión |
| **Require webcam** | Cámara activa durante el intento |
| **Require browser extension** | Exige la extensión Chromium. Por defecto está en No |

El estudiante debe haber completado la **verificación de identidad** (cédula + rostro) antes del intento si el enrollment está habilitado en el núcleo.

### Durante el intento (v1.6.4)

1. `proctor.js` activa la webcam.
2. Compara el rostro con la foto de enrollment (`verify_quiz_identity`). El motor por defecto es **OpenCV local**. Rekognition se usa solo si está configurado y elegido en los ajustes.
3. Repite la comparación cada 90 segundos.
4. Si no coincide y **Bloquear el intento si la identidad no coincide** está activo, muestra un overlay. Si no, registra la incidencia y muestra una alerta.
5. Pide pantalla completa y compartir el monitor. Bloquea el menú contextual y los atajos Ctrl/Cmd+C, V, X, A, P, S, U y F12.
6. Si la extensión Chrome está instalada, recibe avisos de pestañas de IA y de un segundo monitor.

Ajustes del plugin: **Administración → Cuestionarios → Moodle Proctor quiz access rule**.

---

## Suite MOODLE_53

| Repo | Rol |
|------|-----|
| [moodle_proctor](https://github.com/Johnrivera7/moodle_proctor/tree/MOODLE_53) | Núcleo |
| **moodle_quizaccess_proctor** | ← Este plugin |
| [moodle_block_proctor](https://github.com/Johnrivera7/moodle_block_proctor/tree/MOODLE_53) | Bloque |
| [moodle_proctor_python](https://github.com/Johnrivera7/moodle_proctor_python/tree/MOODLE_53) | Python legacy |
| [moodle_proctor_extension](https://github.com/Johnrivera7/moodle_proctor_extension/tree/MOODLE_53) | Extensión |

[Guía de pruebas](https://github.com/Johnrivera7/moodle_proctor/blob/MOODLE_53/TESTING.md)

---

## Licencia

GPL-3.0+ — [LICENSE](LICENSE)

## Soporte

- **Autor:** [John Rivera](https://github.com/Johnrivera7) · [johnriveragonzalez7@gmail.com](mailto:johnriveragonzalez7@gmail.com)
- **Issues:** https://github.com/Johnrivera7/moodle_quizaccess_proctor/issues
