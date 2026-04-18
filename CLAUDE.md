# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Skills

Folge den JardisCore Skills: [CLAUDE.md](~/.claude/CLAUDE.md)

## Package

`jardisadapter/eventdispatcher` — PSR-14 Event Dispatcher fuer synchrone Domain Events in DDD-Anwendungen. Keine externen Dependencies ausser `psr/event-dispatcher ^1.0`.

## Commands

Alle Befehle via Makefile (Docker-basiert, `headgent/phpcli:8.3`):

```bash
make phpunit              # Tests ausfuehren
make phpunit-coverage     # Tests mit Coverage-Text
make phpstan              # Statische Analyse (Level 8)
make phpcs                # Coding Standards (PSR-12)
make install              # Composer install
```

Einzelnen Test ausfuehren:
```bash
docker compose run --rm phpcli vendor/bin/phpunit tests/Unit/EventDispatcherTest.php
```

## Architecture

Vier Klassen, keine Unterverzeichnisse:

- **EventDispatcher** — Implementiert `EventDispatcherInterface` (PSR-14). Nimmt einen `ListenerProviderInterface` entgegen, iteriert dessen Listener, respektiert `StoppableEventInterface`.
- **ListenerProvider** — Implementiert `ListenerProviderInterface` (PSR-14). Fluent API (`listen`/`remove`), Priority-Ordering (hoeher = zuerst), Typ-Hierarchie-Matching (Listener auf Interface/Parent-Klasse empfaengt alle implementierenden Events).
- **Event** — Abstrakte Basis-Klasse fuer Domain Events. Implementiert `StoppableEventInterface` mit `stopPropagation()`/`isPropagationStopped()`. Optional — Events muessen diese Klasse nicht erweitern.
- **EventCollector** — Sammelt Events fuer deferred Dispatch (`record` → `dispatchAll`). Ergaenzung zum `ContextResponse::addEvent()` Pattern im Kernel.

## Foundation-Integration

Der EventDispatcher wird ueber `DomainApp::eventDispatcher()` in den `DomainKernel` eingebunden. BoundedContexts greifen via `$this->resource()->eventDispatcher()` darauf zu. Drei-Zustands-Semantik: Dispatcher-Instanz | `null` (nicht installiert) | `false` (explizit deaktiviert).

## Design-Entscheidungen

- **Kein EventSubscriberInterface** — `static`-basiertes Subscriber-Pattern widerspricht Saeule 5 (explizite Abhaengigkeiten). Listener werden explizit via `listen()` registriert.
- **Kein async Dispatch** — Synchron by Design. Fuer Cross-Process Events → `jardisadapter/messaging`.
- **Kein Event Store/Sourcing** — Persistierung ist Domain/Infrastructure-Aufgabe.
- **Kein Listener-Discovery** — Kein Classpath-Scanning, kein Reflection. Explizite Registrierung.
- **Keine .env-Konfiguration** — Der Dispatcher hat keine deployment-abhaengigen Parameter. Konfiguration erfolgt programmatisch.
