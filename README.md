# <img src="docs/chile.svg" height="22" alt="Bandera de Chile"> Proctor Chile — Quiz Access Rule

[![Moodle](https://img.shields.io/badge/Moodle-5.3-orange?style=flat-square&logo=moodle)](https://moodle.org)
[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL--3.0-blue?style=flat-square)](LICENSE)
[![Branch](https://img.shields.io/badge/Branch-MOODLE__53-green?style=flat-square)](https://github.com/Johnrivera7/moodle_quizaccess_proctor/tree/MOODLE_53)
[![Depends on](https://img.shields.io/badge/Requires-local__moodle__proctor-red?style=flat-square)](https://github.com/Johnrivera7/moodle_proctor)
[![Version](https://img.shields.io/badge/Version-1.8.0-informational?style=flat-square)](version.php)

Regla de acceso a cuestionarios: webcam, **verificación periódica de identidad** y **detección de más de una persona o ausencia** (motor local, Amazon Rekognition o mixto), **reglas por cuestionario** para cada tipo de incidente (registrar, notificar o **bloquear el intento en el servidor**), bloqueo del navegador y alertas en vivo.

| | |
|---|---|
| **Autor** | [John Rivera](https://github.com/Johnrivera7) |
| **Contacto** | [johnriveragonzalez7@gmail.com](mailto:johnriveragonzalez7@gmail.com) |
| **Componente** | `quizaccess_moodle_proctor` |
| **Instalación** | `mod/quiz/accessrule/moodle_proctor/` |
| **Repositorio** | https://github.com/Johnrivera7/moodle_quizaccess_proctor |
| **Rama** | **`MOODLE_53`** (por defecto) — Moodle 5.3 / PHP 8.5 |

> Moodle 5.2: rama [`MOODLE_52`](https://github.com/Johnrivera7/moodle_quizaccess_proctor/tree/MOODLE_52). Moodle 4.5: rama [`MOODLE_45`](https://github.com/Johnrivera7/moodle_quizaccess_proctor/tree/MOODLE_45).

---

## Requisitos

| Requisito | Versión |
|-----------|---------|
| Moodle | **5.3** (`2026100500`) |
| PHP | **8.5** |
| Plugin núcleo | [`local_moodle_proctor`](https://github.com/Johnrivera7/moodle_proctor/tree/MOODLE_53) **v1.8.0** |

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

**Editar cuestionario → Supervisión (Proctor Chile)** — sección propia, justo después de *General*.

| Opción | Descripción |
|--------|-------------|
| **Habilitar supervisión** | Activa la supervisión en el cuestionario |
| **Requerir cámara** | El examen queda cubierto si no se permite la cámara |
| **Requerir extensión del navegador** | El examen se cubre si la extensión no responde (necesaria para detectar herramientas de IA) |
| **Requerir pantalla completa** | Sí por defecto. El navegador sale de pantalla completa en cada cambio de página |
| **Requerir compartir la pantalla completa** | No por defecto. El navegador corta la pantalla compartida en cada cambio de página; conviene con varias preguntas por página |

### Qué hacer ante cada incidente

Para cada tipo de incidente se elige la acción y desde qué vez se aplica en el mismo intento (lista de 1.ª a 20.ª vez):

| Incidente | Por defecto |
|-----------|-------------|
| Más de una persona frente a la cámara | Bloquear desde la 1.ª vez |
| Otra persona en lugar del estudiante | Bloquear desde la 1.ª vez |
| Estudiante ausente frente a la cámara | Notificar desde la 2.ª captura seguida |
| Cambio de pestaña o salida de la ventana | Notificar desde la 3.ª vez |
| Uso de herramientas de IA | Bloquear desde la 1.ª vez |
| Copiar, pegar o atajos de teclado | Solo registrar |
| Salir de pantalla completa, dejar de compartir pantalla o monitores extra | Notificar desde la 3.ª vez |
| Total de incidentes graves | Solo registrar |

- **Solo registrar:** queda en el informe.
- **Notificar al docente:** además avisa al docente y al estudiante (mensaje de Moodle y correo) y guarda la foto como evidencia.
- **Bloquear el examen:** además el servidor bloquea el intento (no se pueden ver las preguntas ni autoguardar, ni iniciar otro intento del mismo cuestionario) hasta que un docente, gestor o administrador conceda otra oportunidad.

El estudiante debe haber completado la **verificación de identidad** (cédula + rostro) antes del intento si la inscripción está habilitada en el núcleo.

### Durante el intento (v1.7.0)

1. `proctor.js` activa la cámara y espera una imagen real (no los primeros fotogramas oscuros).
2. **Verificación de identidad** al entrar y luego cada intervalo configurado en el núcleo (5 min por defecto). Las recargas de página no la repiten.
3. **Control de rostros** cada 30 s por defecto (motor local, sin costo): más de una persona o nadie frente a la cámara.
4. Cada hallazgo y cada incidente del navegador se evalúa con las reglas del cuestionario; si corresponde, el servidor bloquea el intento y el estudiante ve una capa que no se puede cerrar.
5. El personal ve en la página del cuestionario el botón **Infracciones de supervisión** y la pestaña **Más → Supervisión**, con la evidencia y **Conceder otra oportunidad**.
6. Con la extensión: bloqueo de copiar/pegar, cierre de pestañas de IA, aviso de segundo monitor y de salida del navegador.

El consentimiento previo se pide una vez por intento.

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
