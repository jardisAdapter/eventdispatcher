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
- **EventCollector** — Sammelt Events fuer deferred Dispatch (`record` → `dispatchAll`). Ergaenzung zum `ContextResponse::addEvent()` Pattern. `ContextResponse` ist seit der Kernel-Entkopplung keine Kernel-Klasse mehr, sondern wird pro Domain erzeugt (`{Domain}\Response\`); der Vertrag dazu liegt in `jardissupport/contract` (`Kernel\ContextResponseInterface`).

## Kernel-Integration

`jardiscore/foundation` ist geloescht. Das ENV-Bootstrapping ist nach `jardiscore/kernel` gewandert (Bootstrap-Packer `Bootstrap\BuildDomainKernelFromEnv`), der den Dispatcher ueber `Bootstrap\Handler\BuildEventListenerProviderFromEnv` (baut den `ListenerProvider`) und `Bootstrap\Handler\BuildEventDispatcherFromProvider` (wrapt ihn zum PSR-14-Dispatcher) zusammensetzt. Zugriff ueber den Koffer: `$kernel->eventDispatcher(): ?EventDispatcherInterface` (`DomainKernel.php:81`) sowie `$kernel->eventListenerRegistry(): ?EventListenerRegistryInterface` (`DomainKernel.php:86`, dieselbe `ListenerProvider`-Instanz). In generierten Verhaltensklassen (erweitern `{Domain}Context`): `$this->resource()->eventDispatcher()`. Rueckgabe ist Instanz oder `null` — einen dritten Zustand (`false`) gibt es nicht.

## Design-Entscheidungen

- **Kein EventSubscriberInterface** — `static`-basiertes Subscriber-Pattern widerspricht Saeule 5 (explizite Abhaengigkeiten). Listener werden explizit via `listen()` registriert.
- **Kein async Dispatch** — Synchron by Design. Fuer Cross-Process Events → `jardisadapter/messaging`.
- **Kein Event Store/Sourcing** — Persistierung ist Domain/Infrastructure-Aufgabe.
- **Kein Listener-Discovery** — Kein Classpath-Scanning, kein Reflection. Explizite Registrierung.
- **Keine .env-Konfiguration** — Der Dispatcher hat keine deployment-abhaengigen Parameter. Konfiguration erfolgt programmatisch.
