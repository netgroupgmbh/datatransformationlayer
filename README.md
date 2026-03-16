# Data Transformation Layer

[![Lizenz](https://img.shields.io/badge/Lizenz-Apache--2.0-blue.svg)](LICENSE)
![PHP](https://img.shields.io/badge/PHP-%5E8.3-777bb4.svg)
![Contao](https://img.shields.io/badge/Contao-%5E5.3-ea5d0b.svg)

Eine schlanke **Data Transformation Layer** für Contao-Bundles: Du definierst **Projections** (ausgabespezifische Feld-Transformationen) und wendest sie effizient auf große DBAL-Resultsets an – inklusive **Prefetching**, um N+1-Queries zu vermeiden.

---

## Inhalt

- [Motivation](#motivation)
- [Features](#features)
- [Voraussetzungen](#voraussetzungen)
- [Installation](#installation)
- [Konfiguration (Symfony Services)](#konfiguration-symfony-services)
- [Quickstart](#quickstart)
- [Benutzung](#benutzung)
  - [1) Projection erstellen](#1-projection-erstellen)
  - [2) Converter erstellen](#2-converter-erstellen)
  - [3) Transformer anwenden](#3-transformer-anwenden)
  - [4) Prefetching (N+1 vermeiden)](#4-prefetching-n1-vermeiden)
- [Fehler- & Null-Policy](#fehler--null-policy)
- [Architektur (kurz)](#architektur-kurz)
- [Lexikon (Begriffe)](#lexikon-begriffe)
- [Testing & Quality](#testing--quality)
- [Contributing](#contributing)
- [Lizenz](#lizenz)

---

## Motivation

In realen Contao-Projekten werden Daten häufig über **komplexe DBAL-Abfragen** geladen. Bevor die Daten ausgegeben
werden können (Twig-Templates, Exporte, APIs), müssen Werte oft transformiert werden:

- Einfaches Formatieren (z. B. `tstamp -> "12.03.2026"`)
- Ersetzen technischer Werte durch Anzeige-Werte (z. B. `member_id -> "Jane Doe"`)
- Normalisieren/Ableiten von Feldern (z. B. Status-Labels, berechnete Summen)
- Anreichern mit Lookup-Daten aus anderen Tabellen

Eine naive Implementierung verteilt diese Logik oft über Controller, Templates und Repositories. Das führt schnell zu:

- **starker Kopplung** zwischen Query-Logik und Darstellung
- **Duplikation**, sobald mehrere Ausgaben unterschiedliche Formate benötigen (Liste vs. Export vs. API)
- **Performance-Problemen**, insbesondere N+1-Queries, wenn Fremdschlüssel pro Zeile einzeln aufgelöst werden
- fragiler Konfiguration durch „stringly typed“ Arrays

**Lösung:**
Dieses Paket liefert eine **Projection-basierte Transformation Layer**:

- Eine *Projection* ist eine PHP-Klasse (Symfony-Service), die definiert, *welche Felder* für eine Ausgabe transformiert werden.
- Ein *Converter* ist ein wiederverwendbarer Feld-Transformer (Formatting, Lookup, Mapping).
- Converter mit Prefetching können benötigte Lookup-Daten **einmal pro Dataset** laden und so N+1 verhindern.

Dadurch bleiben DBAL-Queries sauber, Ausgabelogik ist explizit und wiederverwendbar, und große Datenmengen können effizient verarbeitet werden.

---

## Features

- Projection-Definitionen als **PHP-Klassen** (keine „Array of strings“-Konfiguration)
- Converter-Referenzen via `::class` (refactor-freundlich, IDE-sicher)
- Optionales **Prefetching** gegen N+1 Lookups
- Für **DBAL-Result-Arrays** gedacht (`fetchAllAssociative()`)
- Symfony-Integration über **Tagged Services** + `ServiceLocator`
- Geeignet für tausende Datensätze (Request-lokales Caching möglich)

---

## Voraussetzungen

- PHP: `^8.3`
- Contao: `contao/core-bundle ^5.3`

---

## Installation

Installation per Composer in deinem Contao-Projekt:

```bash
composer require netgroup/datatransformationlayer
```

Alternativ kannst du das Paket (`netgroup/datatransformationlayer`) über den **Contao Manager** installieren.

---

## Konfiguration (Symfony Services)

### `services.yaml` (Beispiel)

Lege die Datei `config/services.yaml` in deinem Bundle an (oder ergänze sie):

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: false

  # --- Converter ---
  App\Conversion\Converter\:
    resource: '../../Classes/Conversion/Converter/*'
    tags: ['netgroup.datatransformation.converter']

  # --- Projections ---
  App\Conversion\Projection\:
    resource: '../../Classes/Conversion/Projection/*'
    tags: ['netgroup.datatransformation.projection']
```

---

## Quickstart

1. Projection-Klasse erstellen (z. B. `member_list`)
2. Converter-Klassen erstellen (z. B. Datum formatieren, Lookups)
3. DBAL-Resultset mit `DatasetTransformer` transformieren

---

## Benutzung

### 1) Projection erstellen

Eine Projection definiert **welche Felder** für eine bestimmte Ausgabe transformiert werden sollen.

Beispiel: `MemberListProjection` transformiert:

- `tstamp` (timestamp/string) → formatiertes Datum
- `id` → lesbarer Fullname (aus `firstname` + `lastname`)

```php
<?php

namespace App\Conversion\Projection;

use App\Conversion\Definition\ProjectionPlanBuilder;
use App\Conversion\Converter\DateTimeFormatConverter;
use App\Conversion\Converter\MemberFullnameConverter;

final class MemberListProjection implements ProjectionInterface
{
    public function name(): string
    {
        return 'member_list';
    }

    public function build(ProjectionPlanBuilder $builder): void
    {
        $builder->field(OrderListField::CreatedAt)
                //->convert(TrimConverter::class) // mehrere Konvertierungsschritte möglich!
                ->convert(DateTimeFormatConverter::class, ['format' => 'd.m.Y H:i']);
    }
}
```

---

### 2) Converter erstellen

Converter implementieren `FieldConverterInterface`. Sie erhalten:

- den aktuellen Feldwert,
- die komplette Zeile (`$row`),
- einen `ConversionContext` (z. B. Zeitzone),
- optionale Parameter (`$params`).

#### Beispiel A: Datum formatieren (`DateTimeFormatConverter`)

```php
<?php

namespace App\Conversion\Converter;

use App\Conversion\Definition\ConversionContext;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DateTimeFormatConverter implements FieldConverterInterface
{
    public function convert(mixed $value, array $row, ConversionContext $context, array $params = []): mixed
    {
        $params = $this->resolve($params);

        if ($value === null || $value === '') {
            return null;
        }

        $timezone = new DateTimeZone((string) $context->option('timezone', 'UTC'));

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $dt = (new DateTimeImmutable('@' . (string) $value))->setTimezone($timezone);
            return $dt->format($params['format']);
        }

        $dt = new DateTimeImmutable((string) $value, $timezone);
        return $dt->format($params['format']);
    }

    private function resolve(array $params): array
    {
        $r = new OptionsResolver();
        $r->setDefaults(['format' => 'c']);
        $r->setAllowedTypes('format', 'string');

        return $r->resolve($params);
    }
}
```

#### Beispiel B: Fullname aus `tl_member` bauen (`MemberFullnameConverter`)

Dieser Converter zeigt bewusst ein Pattern, das in DBAL-Readmodels häufig ist:
Der Converter kann auch dann funktionieren, wenn er nur die Row bekommt (kein Lookup nötig).

```php
<?php

namespace App\Conversion\Converter;

use App\Conversion\Definition\ConversionContext;

final class MemberFullnameConverter implements FieldConverterInterface
{
    public function convert(mixed $value, array $row, ConversionContext $context, array $params = []): mixed
    {
        $firstname = trim((string) ($row['firstname'] ?? ''));
        $lastname  = trim((string) ($row['lastname'] ?? ''));

        $full = trim($firstname . ' ' . $lastname);

        // Policy: wenn kein Name vorhanden, dann null (konfigurierbar über euer Projekt)
        return $full !== '' ? $full : null;
    }
}
```

---

### 3) Transformer anwenden

In deinem Controller/Service lädst du Daten per DBAL und transformierst sie mit dem Transformer.

```php
<?php

namespace App\Controller;

use App\Conversion\DatasetTransformer;
use App\Conversion\Definition\ConversionContext;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class MemberController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly DatasetTransformer $transformer,
    ) {}

    public function list(): Response
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('m.id', 'm.tstamp', 'm.firstname', 'm.lastname', 'm.email')
            ->from('tl_member', 'm')
            ->where('m.disable != 1')
            ->setMaxResults(5000)
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $context = new ConversionContext(
            projection: 'member_list',
            options: ['timezone' => 'Europe/Berlin']
        );

        $rows = $this->transformer->transform($rows, 'member_list', $context);

        return $this->render('member/list.html.twig', [
            'rows' => $rows,
        ]);
    }
}
```

---

### 4) Prefetching (N+1 vermeiden)

Wenn ein Converter Lookup-Daten benötigt (z. B. `pid -> Titel aus tl_page` oder IDs zu Labels), implementiere `PrefetchingConverterInterface`.

**Prinzip:**
- Beim Transformieren ruft der Transformer *einmalig* `prefetch($rows, $context, $params)` auf.
- Der Converter baut eine Map (z. B. `id => label`).
- Pro Row wird dann nur noch über die Map aufgelöst (keine DB pro Zeile).

Beispiel: `PageTitleLookupConverter` (Lookup `tl_page.id -> tl_page.title`)

```php
<?php

namespace App\Conversion\Converter;

use App\Conversion\Definition\ConversionContext;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class PageTitleLookupConverter implements FieldConverterInterface, PrefetchingConverterInterface
{
    /** @var array<int, string> */
    private array $titleById = [];

    public function __construct(private readonly Connection $connection) {}

    public function prefetch(array $rows, ConversionContext $context, array $params = []): void
    {
        $ids = [];

        foreach ($rows as $row) {
            $id = $row['pid'] ?? null;
            if (is_numeric($id)) {
                $ids[(int) $id] = true;
            }
        }

        $ids = array_keys($ids);

        if ($ids === []) {
            $this->titleById = [];
            return;
        }

        $result = $this->connection->createQueryBuilder()
            ->select('p.id', 'p.title')
            ->from('tl_page', 'p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $map = [];

        foreach ($result as $row) {
            $map[(int) $row['id']] = (string) $row['title'];
        }

        $this->titleById = $map;
    }

    public function convert(mixed $value, array $row, ConversionContext $context, array $params = []): mixed
    {
        if (!is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        // Policy: unbekannte IDs -> null (siehe Abschnitt "Fehler- & Null-Policy")
        return $this->titleById[$id] ?? null;
    }
}
```

---

## Fehler- & Null-Policy

Damit Ausgaben stabil und vorhersehbar sind, sollte im Projekt eine klare Policy gelten. Dieses Paket ist bewusst flexibel; du solltest dich aber **explizit** für ein Verhalten entscheiden.

### Empfohlene Default-Policy

- **Ungültige Eingabewerte** (z. B. leere Strings, falscher Typ)
  → `null` zurückgeben (statt Exception), sofern es ein „Display Field“ ist.

- **Lookup nicht gefunden** (z. B. ID existiert nicht in `tl_page`)
  → `null` zurückgeben *oder* einen Fallback liefern (z. B. `"(unbekannt)"`).

- **Hard-Fail nur bei echten Programmfehlern**
  → z. B. wenn Pflicht-Parameter fehlen (OptionsResolver), oder die Projection-Konfiguration inkonsistent ist.

### Empfehlung für Exporte/APIs

Für Exporte oder APIs ist `null` manchmal unpraktisch. Typische Alternativen:

- String-Fallback: `"(unbekannt)"`
- Technischer Fallback: `"#{$id}"` (z. B. `#123`)
- Separates Feld: `pageTitle` + `pageTitleResolved=false`

> Tipp: Wenn ihr mehrere Ausgaben habt (Liste vs. Export), löst ihr das sauber über **verschiedene Projections**.

---

## Architektur (kurz)

Das Paket trennt bewusst drei Ebenen:

1. **Query Layer (DBAL / Read Model)**
   - Liefert rohe Arrays (z. B. `fetchAllAssociative()`).
   - Keine Formatierung/Lookup-Logik.

2. **Transformation Layer (Projections + Converter)**
   - Eine Projection definiert transformationsregeln pro Ausgabe (Liste/Export/API).
   - Converter sind kleine, wiederverwendbare Bausteine.
   - Prefetching verhindert N+1.

3. **Presentation Layer (Twig/JSON/Export)**
   - Bekommt „ready-to-display“ Daten.
   - Keine DB-Lookups mehr nötig.

---

## Lexikon (Begriffe)

- **Dataset**: Eine Liste von Datensätzen/Zeilen, typischerweise das Ergebnis von `fetchAllAssociative()`.
- **Row**: Ein einzelner Datensatz (assoziatives Array).
- **Projection**: Ausgabespezifische Definition *welche Felder* wie transformiert werden.
- **ProjectionPlan**: Internes, „kompiliertes“ Regelwerk einer Projection.
- **Converter**: Baustein, der einen Feldwert transformiert (Formatierung, Mapping, Lookup, …).
- **Pipeline**: Mehrere Converter-Schritte hintereinander für ein Feld (z. B. `trim` → `map` → `format`).
- **Prefetching**: Einmaliges Vorladen von Lookup-Daten pro Dataset, um N+1 zu verhindern.
- **ConversionContext**: Kontextinformationen (z. B. `timezone`, „caller“-Optionen), die Converter nutzen können.

---

## Testing & Quality

Dieses Projekt ist für saubere Softwarequalität ausgelegt und kann u. a. mit folgenden Tools abgesichert werden:

- **PHPUnit** für Unit- & Integration-Tests
- **PHPStan** für statische Analyse
- **Easy Coding Standard** für Coding-Standards

> CI läuft typischerweise in GitLab CI (nicht öffentlich einsehbar).

---

## Contributing

Beiträge sind willkommen:

1. Fork erstellen
2. Feature-Branch anlegen (`feature/...`)
3. Änderungen mit Tests/Analyse absichern
4. Merge Request eröffnen

Bitte achte auf:
- klare, kleine Commits
- konsistente Namensgebung
- verständliche Tests (Arrange/Act/Assert)

---

## Lizenz

Dieses Projekt ist unter der **Apache License 2.0** lizenziert. Siehe `LICENSE`.
