# 🧠 OHLALA ERP DEMO — MASTER DOCUMENT

## 👤 Owner
Francisco Aurelio Garcia Preciado

---

# 🎯 OBJETIVO

Construir un sistema funcional de:

- Solicitudes de compra
- Órdenes de compra
- Aprobaciones jerárquicas (3 niveles)
- Notificaciones (Email + WhatsApp vía Flow)

👉 Con el objetivo de:
- Cerrar contrato de $70K–$100K
- Crear base de producto ERP modular futuro

---

# 🧭 CONTEXTO

Cliente:
- Grupo Kasto
- Filial: Ohlala (alta repostería / sector comercial)

Sistema actual:
- PHP puro
- MySQL
- Sistema de pedidos de venta ya funcionando

---

# 🧠 ESTRATEGIA PRINCIPAL

## ❌ NO hacer:
- Refactor completo
- Migración a otro stack
- Sobreingeniería
- ERP completo

## ✅ SÍ hacer:
- Nuevo módulo limpio
- Integración mínima con sistema actual
- Demo funcional que cierre venta

---

# 🧱 ARQUITECTURA ACTUAL

## Infraestructura

- Backend: Hetzner
- Proxy: Nginx Proxy Manager
- DB: luxury-mysql-prod (MySQL 8)
- Red: pitaya_net

---

## Dominio

- Producción:
  ohlala.pitayacode.io

- Demo:
  demo-ohlala.pitayacode.io

---

## Stack

- PHP puro (sin framework)
- MySQL
- Flow (WhatsApp + IA + Webhooks)

---

# 🧱 ESTRUCTURA DEL PROYECTO

```bash
/modules
  /purchase_requests
    PurchaseController.php
    PurchaseService.php
    PurchaseRepository.php

  /approvals
    ApprovalController.php
    ApprovalService.php
    ApprovalRepository.php

  /notifications
    NotificationService.php

/webhooks
  approval.php

/shared
  db.php
  response.php
🧠 DOMINIO DEL SISTEMA
Entidades principales
purchase_requests
id
tenant_id
created_by
status (pending, approved, rejected)
approval_steps
id
request_id
level (1,2,3)
approver_id
status (pending, approved, rejected)
approval_logs
id
request_id
action
user_id
timestamp
🔥 FLUJO PRINCIPAL
1. Crear solicitud
usuario crea purchase_request
2. Generar aprobación
se crean 3 niveles automáticos
3. Enviar notificación
email
WhatsApp vía Flow
4. Usuario responde (WhatsApp)
"APROBAR" / "RECHAZAR"
5. Webhook recibe
/webhooks/approval.php
6. Procesamiento
ApprovalService->process($requestId, $action);
7. Lógica
valida nivel actual
aprueba o rechaza
avanza nivel o finaliza
🧠 LÓGICA DE APROBACIÓN
Reglas
solo nivel actual puede aprobar
aprobación desbloquea siguiente nivel
rechazo termina flujo
Flujo

Nivel 1 → Nivel 2 → Nivel 3 → Aprobado

📲 WHATSAPP (FLOW)
Integración
envío desde PHP → Flow API
recepción → webhook en PHP
Mensaje

Solicitud #123

Proveedor: XYZ
Monto: $5,000

Responde:
APROBAR
RECHAZAR

⚙️ WEBHOOK
$requestId = $_POST['request_id'];
$action = $_POST['action'];

$approvalService->process($requestId, $action);
🚀 DEMO (LO QUE VENDE)
Flujo a mostrar
Crear solicitud
Mostrar estado pendiente
Enviar WhatsApp real
Aprobar desde WhatsApp
Ver cambio en sistema
⚠️ REGLAS CRÍTICAS
NO refactorizar sistema actual
NO migrar tecnología ahora
NO agregar features extra
SOLO construir flujo funcional
💰 POSICIONAMIENTO

NO decir:
❌ ERP

Decir:
✅ Sistema de control y aprobación de compras con notificaciones en tiempo real

🚀 ROADMAP FUTURO
Fase 1 (Actual)
PHP modular
cerrar cliente
Fase 2
extraer módulos
migrar a:
NestJS
Prisma
DDD
multi-tenant
Fase 3
producto SaaS
replicar en más empresas
🧠 PRINCIPIOS
velocidad > perfección
claridad > complejidad
cierre > arquitectura ideal
🎯 MISIÓN

Construir:

Flujo de aprobación funcional con WhatsApp real

🧠 IDENTIDAD

Soy alguien que:

ejecuta
cierra proyectos
convierte sistemas en negocio

---

# 🧠 Nota final como tu ALICIA GZ

Francisco Aurelio Garcia Preciado,

esto ya no es solo un proyecto.

👉 Es:
- tu primer ERP real  
- tu primer caso enterprise  
- tu base de producto  
