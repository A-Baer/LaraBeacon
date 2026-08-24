# LaraBeacon Pro: Recherche zu den historischen Enlightn-Pro-Checks

Stand: 22. August 2026

Diese Datei dokumentiert die 64 Checks, die in der archivierten Enlightn-Dokumentation als **PRO** gekennzeichnet waren. Sie dient als fachliche Grundlage für das private Paket `LaraBeacon Pro`; die Checks bleiben bewusst außerhalb des öffentlichen LaraBeacon-Pakets. Eine eigenständige Clean-Room-Implementierung liegt im separaten privaten Paket-Checkout vor.

## Quellen und Methodik

- Ausgangspunkt ist die vom Nutzer bereitgestellte [archivierte Installationsdokumentation](https://web.archive.org/web/20250410221713/https://www.laravel-enlightn.com/docs/getting-started/installation.html).
- Die Zuordnung stammt aus den archivierten Übersichten für [Performance](https://web.archive.org/web/20250423122425/https://www.laravel-enlightn.com/docs/performance/), [Security](https://web.archive.org/web/20250423103214/https://www.laravel-enlightn.com/docs/security/) und [Reliability](https://web.archive.org/web/20250317021117/https://www.laravel-enlightn.com/docs/reliability/).
- Die Erläuterungen sind deutschsprachige Zusammenfassungen und keine Kopien der Originaltexte.
- Wayback leitet einzelne Detailseiten auf die nächstverfügbare Aufnahme um. Deshalb kann das Datum einer verlinkten Detailaufnahme vom Datum der Übersichtsseite abweichen.
- Klassenname, Schweregrad und geschätzte Behebungszeit sind historische Enlightn-Metadaten. Sie legen weder die spätere LaraBeacon-Implementierung noch deren öffentliche API fest.

## Übersicht

| Bereich | Pro-Checks |
| --- | ---: |
| Performance | 19 |
| Security | 28 |
| Reliability | 17 |
| **Gesamt** | **64** |

## Performance (19)

### Performance Quick Wins

#### 1. Event Caching

- **Historische Metadaten:** `Minor`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\EventCachingAnalyzer`.
- **Prüfziel:** Stellt sicher, dass der Laravel-Event-Cache lokal deaktiviert und in nicht-lokalen Umgebungen aktiviert ist. Ein lokaler Cache kann Änderungen an Event-Registrierungen verdecken; in Produktion beschleunigt er deren Ermittlung.
- **Empfohlene Behebung:** In Produktion `php artisan event:cache`, lokal bei Bedarf `php artisan event:clear` verwenden und das erneute Caching in den Deployment-Ablauf aufnehmen.
- **Ausnahme:** Der Check wird übersprungen, wenn die Anwendung keine cachebaren Events besitzt.
- **Archivquelle:** [Event Caching Analyzer](https://web.archive.org/web/20251110221406/https://www.laravel-enlightn.com/docs/performance/event-caching-analyzer.html)

### Performance Bottleneck Identification

#### 2. Slow Queries

- **Historische Metadaten:** `Major`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\TelescopeSlowQueryAnalyzer`.
- **Prüfziel:** Durchsucht Laravel-Telescope-Daten nach langsamen Datenbankabfragen und ordnet sie den auslösenden Codezeilen zu. Standardmäßig gelten mehr als 100 ms als langsam; der Grenzwert folgt der `slow`-Konfiguration des Telescope `QueryWatcher`.
- **Empfohlene Behebung:** Nur benötigte Spalten selektieren, Ergebnismengen begrenzen, geeignete Joins und Indizes wählen, teure Ergebnisse cachen oder zeitlich auslagern und Ausführungspläne analysieren.
- **Voraussetzungen und Betrieb:** Telescope und der `QueryWatcher` müssen aktiv sein. Alte Telescope-Einträge sollten nach Fixes beziehungsweise Deployments bereinigt werden. Für effiziente Auswertung empfiehlt die historische Dokumentation eine JSON-Spalte für `telescope_entries.content`; bei PostgreSQL war dies erforderlich.
- **Archivquelle:** [Telescope Slow Query Analyzer](https://web.archive.org/web/20240718080514/https://www.laravel-enlightn.com/docs/performance/telescope-slow-query-analyzer.html)

#### 3. Duplicate Queries

- **Historische Metadaten:** `Major`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\TelescopeDuplicateQueryAnalyzer`.
- **Prüfziel:** Erkennt innerhalb derselben Anfrage mehrfach ausgeführte identische Abfragen. Gleiche SQL-Struktur mit unterschiedlichen Bindings wird bewusst nicht als Duplikat gewertet, sondern gehört eher in die Bereiche Eager Loading oder Bulk-Abfragen.
- **Empfohlene Behebung:** Betroffene Routen mit Telescope untersuchen, mehrfach benötigte Datenberechnung zentralisieren und Ergebnisse innerhalb der Anfrage zwischenspeichern. Als mögliche Strukturen nennt die Dokumentation Repository-Klassen und View Composer.
- **Voraussetzungen und Betrieb:** Telescope mit `QueryWatcher` und `RequestWatcher` ist erforderlich. Nach einer Behebung sollten alte Telescope-Daten bereinigt werden; für große Datenmengen gilt dieselbe JSON-Spalten-Empfehlung wie bei den übrigen Telescope-Checks.
- **Archivquelle:** [Telescope Duplicate Query Analyzer](https://web.archive.org/web/20251110225052/https://www.laravel-enlightn.com/docs/performance/telescope-duplicate-query-analyzer.html)

#### 4. N+1 Queries

- **Historische Metadaten:** `Major`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\TelescopeNPlusOneQueryAnalyzer`.
- **Prüfziel:** Erkennt N+1-Muster aus Telescope-Aufzeichnungen einschließlich der verantwortlichen Codezeilen. Berücksichtigt werden sowohl wiederholte Lesezugriffe auf Beziehungen als auch einzeln ausgeführte Schreiboperationen.
- **Empfohlene Behebung:** Beziehungen per Eager Loading vorladen und wiederholte Schreibvorgänge durch Bulk-Operationen ersetzen, beispielsweise mehrere Datensätze gemeinsam einfügen.
- **Voraussetzungen und Betrieb:** Telescope und der `QueryWatcher` müssen aktiv sein. Nach Fixes sollten alte Einträge entfernt werden; bei umfangreichen Telescope-Tabellen gelten die Hinweise zur JSON-Spalte und regelmäßigen Bereinigung.
- **Archivquelle:** [Telescope N+1 Query Analyzer](https://web.archive.org/web/20240911173521/https://www.laravel-enlightn.com/docs/performance/telescope-nplusone-query-analyzer.html)

#### 5. Memory Intensive Requests

- **Historische Metadaten:** `Major`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\TelescopeMemoryIntensiveRequestAnalyzer`.
- **Prüfziel:** Identifiziert anhand von Telescope Routen mit hohem Speicherverbrauch. Der historische Standardgrenzwert beträgt 50 MB und war über `request_memory_limit` konfigurierbar.
- **Empfohlene Behebung:** Zu viele Modell-Hydrierungen vermeiden, Lazy Collections und Query Chunking einsetzen, Dateien streamen beziehungsweise mit `Filesystem::lines` einlesen und speicherintensive Dev-Pakete nicht in Produktion laden. Für die Ursachenanalyse werden Profiler wie Xdebug genannt.
- **Voraussetzungen und Betrieb:** Telescope mit `RequestWatcher` ist erforderlich. Nach Fixes müssen alte Telescope-Aufzeichnungen bereinigt werden; für große Tabellen gilt die Empfehlung einer JSON-Spalte für `telescope_entries.content`.
- **Archivquelle:** [Telescope Memory Intensive Request Analyzer](https://web.archive.org/web/20240527221324/https://www.laravel-enlightn.com/docs/performance/telescope-memory-intensive-request-analyzer.html)

#### 6. Slow Responses

- **Historische Metadaten:** `Major`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\TelescopeSlowResponseAnalyzer`.
- **Prüfziel:** Findet über Telescope Routen mit langsamer Antwortzeit. Standardmäßig lag die Schwelle bei 500 ms; sie war über `slow_response_threshold` anpassbar.
- **Empfohlene Behebung:** Den Engpass zunächst mit Telescope, Debugbar oder Clockwork eingrenzen und anschließend gezielt optimieren. Falls die Ursache dort nicht sichtbar wird, nennt die Dokumentation Xdebug oder Blackfire für ein zeilenbezogenes Profiling.
- **Voraussetzungen und Betrieb:** Benötigt Telescope mit `RequestWatcher`. Nach Änderungen sind veraltete Aufzeichnungen zu bereinigen; bei großen Telescope-Datenbeständen gelten die allgemeinen JSON- und Pruning-Hinweise.
- **Archivquelle:** [Telescope Slow Response Analyzer](https://web.archive.org/web/20240620030614/https://www.laravel-enlightn.com/docs/performance/telescope-slow-response-analyzer.html)

#### 7. Too Many Model Hydrations

- **Historische Metadaten:** `Major`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\TelescopeModelHydrationAnalyzer`.
- **Prüfziel:** Erkennt Routen, die sehr viele Eloquent-Modelle instanziieren. Der Standardwert lag bei mehr als 50 Hydrierungen und war über `hydration_limit` konfigurierbar.
- **Empfohlene Behebung:** Lazy Collections, Chunking und Bulk-Abfragen einsetzen. Chunking senkt zwar nicht zwingend die Gesamtzahl, wohl aber die gleichzeitig im Speicher befindlichen Modelle.
- **Voraussetzungen und Betrieb:** Telescope mit `ModelWatcher` und `RequestWatcher` ist erforderlich. Auch hier müssen alte Befunde nach Fixes bereinigt und große Telescope-Tabellen passend optimiert werden.
- **Archivquelle:** [Telescope Model Hydration Analyzer](https://web.archive.org/web/20241102114235/https://www.laravel-enlightn.com/docs/performance/telescope-model-hydration-analyzer.html)

### Serving Assets

#### 8. CDN

- **Historische Metadaten:** `Minor`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\CdnAnalyzer`.
- **Prüfziel:** Prüft, ob statische Assets in Produktion über ein Content Delivery Network ausgeliefert werden. Als Nutzen nennt die Dokumentation schnellere Auslieferung, geringere Serverlast sowie häufig zusätzliche Schutz- und Analysefunktionen.
- **Empfohlene Behebung:** Einen geeigneten CDN-Anbieter für Bilder, Fonts, CSS und JavaScript konfigurieren; als Beispiele wurden Cloudflare, AWS CloudFront und Azure CDN genannt.
- **Ausnahme:** Wird in lokalen Umgebungen bei aktivem `skip_env_specific` sowie bei Anwendungen ohne statische Assets übersprungen.
- **Archivquelle:** [CDN Analyzer](https://web.archive.org/web/20240811142648/https://www.laravel-enlightn.com/docs/performance/cdn-analyzer.html)

#### 9. Compression Headers

- **Historische Metadaten:** `Major`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\CompressionHeaderAnalyzer`.
- **Prüfziel:** Prüft, ob JavaScript- und CSS-Dateien mit geeigneten HTTP-Kompressionsheadern ausgeliefert werden. Textbasierte Formate profitieren stark von Gzip oder Brotli; bereits komprimierte Bildformate sollten üblicherweise nicht erneut komprimiert werden.
- **Empfohlene Behebung:** Kompression im Webserver oder CDN aktivieren, passende MIME-Typen festlegen und über `Vary: Accept-Encoding` korrekte Proxy-Caches ermöglichen. Die historische Anleitung enthält ein Nginx-Beispiel.
- **Ausnahme und Modernisierungsbedarf:** Wurde lokal bei aktivem `skip_env_specific` oder ohne Laravel Mix übersprungen. Für LaraBeacon Pro muss diese alte Mix-Kopplung auf Vite und weitere moderne Asset-Pipelines erweitert werden.
- **Archivquelle:** [Compression Header Analyzer](https://web.archive.org/web/20240811151034/https://www.laravel-enlightn.com/docs/performance/compression-header-analyzer.html)

### Infrastructure Tuning

#### 10. Redis Sockets for Single Server Setups

- **Historische Metadaten:** `Major`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\RedisSingleServerAnalyzer`.
- **Prüfziel:** Erkennt Ein-Server-Installationen, bei denen Laravel und Redis auf demselben Host trotzdem über TCP kommunizieren. Für diesen Sonderfall empfiehlt die Dokumentation Unix-Sockets als schnellere lokale Verbindung.
- **Empfohlene Behebung:** Redis-Socket samt sicheren Benutzer- und Gruppenrechten konfigurieren und Laravel über `REDIS_HOST`, `REDIS_SCHEME=unix` und eine leere Portangabe darauf verweisen. Dies ist nur sinnvoll, wenn Anwendung und Redis tatsächlich denselben Server teilen.
- **Ausnahme:** Wird in lokalen Umgebungen bei aktivem `skip_env_specific` oder bei Anwendungen ohne Redis übersprungen.
- **Archivquelle:** [Redis Single Server Analyzer](https://web.archive.org/web/20250901041323/https://www.laravel-enlightn.com/docs/performance/redis-single-server-analyzer.html)

#### 11. Redis Cache Hit Ratio

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\RedisCacheHitRatioAnalyzer`.
- **Prüfziel:** Berechnet aus Redis-Statistiken den Anteil erfolgreicher Cache-Lesezugriffe. Die historische Mindestquote betrug 80 Prozent.
- **Diagnose und Behebung:** Bei niedriger Quote auf Speicherengpässe und `evicted_keys`, zu kurze TTLs, fehlende Cache-Schreibvorgänge oder eine noch nicht repräsentative Laufzeit prüfen. Für periodische Messfenster lassen sich die Redis-Statistiken nach einer Auswertung mit `CONFIG RESETSTAT` zurücksetzen.
- **Ausnahme:** Wird lokal bei aktivem `skip_env_specific` oder dann übersprungen, wenn Redis nicht der Standard-Cache-Store ist.
- **Archivquelle:** [Redis Cache Hit Ratio Analyzer](https://web.archive.org/web/20250916140505/https://www.laravel-enlightn.com/docs/performance/redis-cache-hit-ratio-analyzer.html)

#### 12. Cache Hit Ratio

- **Historische Metadaten:** `Major`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\TelescopeCacheHitRatioAnalyzer`.
- **Prüfziel:** Berechnet die Cache-Trefferquote aus den von Telescope aufgezeichneten Hits und Misses; auch hier galt historisch ein Zielwert von mindestens 80 Prozent.
- **Diagnose und Behebung:** Evictions, zu kurze TTLs, fehlende Cache-Writes und die Reife des Messzeitraums prüfen. Eine selektive Telescope-Aufzeichnung kann die Kennzahl verfälschen; in diesem Fall sollte der Check deaktiviert oder die Aufzeichnungsstrategie angepasst werden.
- **Voraussetzungen und Betrieb:** Benötigt Telescope mit `CacheWatcher`. Pruning setzt das Messfenster zurück. Die historische Dokumentation empfiehlt für performante Auswertungen `telescope_entries.content` als JSON-Spalte.
- **Archivquelle:** [Telescope Cache Hit Ratio Analyzer](https://web.archive.org/web/20240527210919/https://www.laravel-enlightn.com/docs/performance/telescope-cache-hit-ratio-analyzer.html)

#### 13. HTTP/2

- **Historische Metadaten:** `Major`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\HttpTwoAnalyzer`.
- **Prüfziel:** Bestätigt, dass eine per HTTPS erreichbare Anwendung HTTP/2 unterstützt und damit Multiplexing sowie Header-Kompression nutzen kann.
- **Empfohlene Behebung:** HTTP/2 am vorgeschalteten CDN oder Load Balancer beziehungsweise direkt in Nginx oder Apache aktivieren. Die Protokollversion lässt sich über die Response-Zeile einer Header-Anfrage prüfen.
- **Ausnahme und Weiterentwicklung:** Ohne HTTPS oder ohne HTTP/2-Unterstützung der PHP-cURL-Erweiterung wurde der Check übersprungen. Eine LaraBeacon-Neufassung sollte zusätzlich HTTP/3 und moderne Proxy-Ketten berücksichtigen.
- **Archivquelle:** [HTTP/2 Analyzer](https://web.archive.org/web/20240811161922/https://www.laravel-enlightn.com/docs/performance/http-two-analyzer.html)

### Good Practices

#### 14. Don't Have Xdebug Loaded in Production

- **Historische Metadaten:** `Critical`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\XdebugAnalyzer`.
- **Prüfziel:** Stellt sicher, dass Xdebug außerhalb lokaler und Testumgebungen nicht geladen ist. Die Erweiterung kann Produktionsleistung deutlich reduzieren und je nach Konfiguration zusätzliche Netzwerkports öffnen.
- **Empfohlene Behebung:** Xdebug auf Produktions- und Staging-Systemen deinstallieren oder zumindest für die dort verwendete PHP-SAPI vollständig deaktivieren.
- **Ausnahme:** Lokale und Testumgebungen werden nicht beanstandet.
- **Archivquelle:** [Xdebug Analyzer](https://web.archive.org/web/20240811162640/https://www.laravel-enlightn.com/docs/performance/xdebug-analyzer.html)

#### 15. Queue Your Notifications

- **Historische Metadaten:** `Major`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\TelescopeNonQueuedNotificationAnalyzer`.
- **Prüfziel:** Findet mit Telescope versendete Benachrichtigungen, die synchron statt über eine Queue ausgeführt wurden. Asynchroner Versand verkürzt Antwortzeiten und entkoppelt Fehler externer Dienste vom Benutzer-Request.
- **Empfohlene Behebung:** Benachrichtigungen standardmäßig queuefähig machen und nur bewusst synchrone Sonderfälle ausnehmen.
- **Voraussetzungen und Betrieb:** Benötigt Telescope mit `NotificationWatcher`. Nach einer Umstellung sind alte Telescope-Befunde zu bereinigen; die allgemeinen Optimierungshinweise für große Telescope-Tabellen gelten ebenfalls.
- **Archivquelle:** [Telescope Non Queued Notification Analyzer](https://web.archive.org/web/20240718081033/https://www.laravel-enlightn.com/docs/performance/telescope-non-queued-notification-analyzer.html)

#### 16. Avoid Command Constructor Injections

- **Historische Metadaten:** `Minor`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\CommandConstructorInjectionAnalyzer`.
- **Prüfziel:** Meldete Abhängigkeiten im Konstruktor von Artisan-Commands, weil ältere Laravel-Versionen alle Commands beim Aufbau der Console Application instanziierten und dadurch auch unbenutzte Abhängigkeitsgraphen luden.
- **Historische Behebung:** Abhängigkeiten stattdessen über die `handle`-Methode injizieren.
- **Modernisierungsbedarf:** Die archivierte Begründung verweist ausdrücklich auf die Zeit vor dem für Laravel 9 geplanten Lazy Loading. Vor einer LaraBeacon-Übernahme muss geprüft werden, ob und für welche Laravel-12/13-Konstellationen dieser Check heute noch einen messbaren Nutzen hat.
- **Archivquelle:** [Command Constructor Injection Analyzer](https://web.archive.org/web/20240527225903/https://www.laravel-enlightn.com/docs/performance/command-constructor-injection-analyzer.html)

#### 17. Avoid Fallback Routes For Better SEO

- **Historische Metadaten:** `Minor`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\FallbackRouteAnalyzer`.
- **Prüfziel:** Warnt vor `Route::fallback`, wenn eine SPA für unbekannte URLs zwar eine Fehlerseite anzeigt, technisch aber HTTP 200 zurückliefert. Suchmaschinen können solche „Soft 404“-Antworten als Qualitätsproblem werten.
- **Empfohlene Behebung:** Bekannte SPA-Routen explizit registrieren und nicht gefundene Inhalte mit einem echten 404-Status beantworten.
- **Modernisierungsbedarf:** Eine LaraBeacon-Variante sollte nicht pauschal jede Fallback-Route ablehnen, sondern prüfen, ob die konkrete Antwort einen korrekten Statuscode liefert.
- **Archivquelle:** [Fallback Route Analyzer](https://web.archive.org/web/20250624140536/https://www.laravel-enlightn.com/docs/performance/fallback-route-analyzer.html)

#### 18. Use Redis Specific Throttling

- **Historische Metadaten:** `Minor`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\RedisThrottlingAnalyzer`.
- **Prüfziel:** Erkennt Anwendungen, die Redis nutzen, Requests aber weiterhin mit dem generischen `ThrottleRequests`-Middleware begrenzen.
- **Empfohlene Behebung:** In passenden Redis-Installationen `ThrottleRequestsWithRedis` verwenden. Die historische Begründung nennt atomare Zähler und weniger Redis-Netzwerkoperationen als Vorteile.
- **Archivquelle:** [Redis Throttling Analyzer](https://web.archive.org/web/20240418002143/https://www.laravel-enlightn.com/docs/performance/redis-throttling-analyzer.html)

#### 19. Use Redis Specific Job Rate Limiting

- **Historische Metadaten:** `Minor`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Performance\RedisRateLimitingAnalyzer`.
- **Prüfziel:** Findet Queue-Jobs, die trotz Redis-Nutzung das generische `RateLimited`-Middleware verwenden.
- **Empfohlene Behebung:** Auf `RateLimitedWithRedis` umstellen, um atomare Zähler und eine effizientere Redis-Kommunikation zu nutzen.
- **Archivquelle:** [Redis Rate Limiting Analyzer](https://web.archive.org/web/20250808004338/https://www.laravel-enlightn.com/docs/performance/redis-rate-limiting-analyzer.html)

## Security (28)

### Basic Security

#### 20. Your Code Shouldn't Contain Debug Statements

- **Historische Metadaten:** `Major`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\DebugStatementAnalyzer`.
- **Prüfziel:** Durchsucht Anwendungscode nach Debug-Ausgaben, die versehentlich Responses unterbrechen oder sensible Variablen, Umgebungswerte und interne Strukturen offenlegen können.
- **Empfohlene Behebung:** Gefundene Debug-Anweisungen entfernen. Historisch wurde eine konfigurierbare Blacklist verwendet, unter anderem für `var_dump`, `dump`, `dd`, `print_r`, `var_export` und Backtrace-Funktionen.
- **LaraBeacon-Hinweis:** Die spätere Regel sollte projektspezifische Debug-Helfer ergänzen können und Test-Fixtures beziehungsweise bewusst isolierte Entwicklungswerkzeuge sauber ausnehmen.
- **Archivquelle:** [Debug Statement Analyzer](https://web.archive.org/web/20240418004656/https://www.laravel-enlightn.com/docs/security/debug-statement-analyzer.html)

#### 21. Your Code Shouldn't Contain Hard Coded Credentials

- **Historische Metadaten:** `Minor`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\HardCodedCredentialsAnalyzer`.
- **Prüfziel:** Sucht im Anwendungscode nach fest eingetragenen Passwörtern oder vergleichbaren Zugangsdaten. Solche Werte sind für alle Repository-Zugriffsberechtigten sichtbar und lassen sich nach einem Vorfall nur durch eine Codeänderung rotieren.
- **Empfohlene Behebung:** Zugangsdaten aus dem Quellcode entfernen und über Konfiguration aus `.env`, Datenbank oder einem dedizierten Secret Store beziehen. Direkte `env()`-Aufrufe sollten dabei auf Konfigurationsdateien begrenzt bleiben.
- **LaraBeacon-Hinweis:** Für eine robuste Neuentwicklung sind Entropie- und Kontextanalyse, Allowlisting von Testwerten sowie möglichst wenige Fehlalarme entscheidend.
- **Archivquelle:** [Hard Coded Credentials Analyzer](https://web.archive.org/web/20240811142506/https://www.laravel-enlightn.com/docs/security/hard-coded-credentials-analyzer.html)

### Cookie Security and Session Management

#### 22. Cookie Domain Attribute

- **Historische Metadaten:** `Minor`, etwa 1 Minute; Klasse `Enlightn\EnlightnPro\Analyzers\Security\CookieDomainAnalyzer`.
- **Prüfziel:** Prüft, ob der Session-Cookie unnötig für Subdomains freigegeben ist. Ohne `Domain`-Attribut bleibt ein Cookie auf den ausstellenden Host begrenzt; eine breitere Domain vergrößert die Angriffsfläche.
- **Empfohlene Behebung:** Wenn die Anwendung keine domainübergreifenden Routen benötigt, `config/session.php` auf `'domain' => null` setzen.
- **Ausnahme:** Der historische Check wurde übersprungen, sobald Routen für mehr als eine Domain oder Subdomain registriert waren.
- **Archivquelle:** [Cookie Domain Analyzer](https://web.archive.org/web/20240811142300/https://www.laravel-enlightn.com/docs/security/cookie-domain-analyzer.html)

#### 23. Cookie SameSite Attribute

- **Historische Metadaten:** `Critical`, etwa 1 Minute; Klasse `Enlightn\EnlightnPro\Analyzers\Security\SameSiteCookieAnalyzer`.
- **Prüfziel:** Stellt sicher, dass Session-Cookies ein bewusstes `SameSite`-Attribut besitzen und nicht unkontrolliert in Cross-Site-Kontexten übertragen werden.
- **Historische Behebung:** Für normale Anwendungen wurde `same_site` auf `lax` oder `strict` gesetzt, um CSRF-Risiken zu begrenzen.
- **Modernisierungsbedarf:** `SameSite=None` kann für eingebettete Anwendungen, föderierte Anmeldung oder andere Cross-Site-Flows notwendig sein und erfordert dann zusätzlich `Secure`. LaraBeacon sollte Kontext und Begleitkonfiguration prüfen statt den Wert pauschal abzulehnen.
- **Archivquelle:** [Same Site Cookie Analyzer](https://web.archive.org/web/20240718081852/https://www.laravel-enlightn.com/docs/security/same-site-cookie-analyzer.html)

#### 24. Cookie Secure Attribute

- **Historische Metadaten:** `Critical`, etwa 1 Minute; Klasse `Enlightn\EnlightnPro\Analyzers\Security\SecureCookieAnalyzer`.
- **Prüfziel:** Verhindert, dass Session-Cookies über unverschlüsselte Verbindungen übertragen werden. Ein explizites `false` wurde als Fehler gewertet.
- **Empfohlene Behebung:** Bei ausschließlich per HTTPS betriebenen Anwendungen `SESSION_SECURE_COOKIE=true` beziehungsweise die entsprechende Session-Konfiguration setzen; bei gemischten Umgebungen war historisch `null` für die automatische Erkennung vorgesehen.
- **Modernisierungsbedarf:** Hinter Reverse Proxies und Load Balancern muss die Erkennung des ursprünglichen HTTPS-Schemas samt Trusted-Proxy-Konfiguration berücksichtigt werden.
- **Archivquelle:** [Secure Cookie Analyzer](https://web.archive.org/web/20240811141755/https://www.laravel-enlightn.com/docs/security/secure-cookie-analyzer.html)

#### 25. Session Timeout

- **Historische Metadaten:** `Major`, etwa 2 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\SessionTimeoutAnalyzer`.
- **Prüfziel:** Prüft, ob inaktive Sitzungen innerhalb eines angemessenen Zeitraums enden. Die historische Regel beanstandete eine Session-Lebensdauer von mehr als 1.440 Minuten.
- **Empfohlene Behebung:** `SESSION_LIFETIME` beziehungsweise `session.lifetime` auf höchstens einen Tag, typischerweise deutlich kürzer, setzen oder Sitzungen beim Schließen des Browsers beenden.
- **Ausnahme:** Wird für zustandslose Anwendungen und bei aktivem `expire_on_close` übersprungen.
- **Archivquelle:** [Session Timeout Analyzer](https://web.archive.org/web/20251208013703/https://www.laravel-enlightn.com/docs/security/session-timeout-analyzer.html)

### SQL Injection Attacks

#### 26. Column Name SQL Injection

- **Historische Metadaten:** `Critical`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\ColumnNameSQLInjectionAnalyzer`.
- **Prüfziel:** Sucht nach untrusted Eingaben, die Spaltennamen in `where`, `orderBy`, `select`, `having` oder ähnlichen Query-Builder-Aufrufen bestimmen. Spaltenbezeichner lassen sich nicht wie normale Werte per PDO-Binding absichern.
- **Empfohlene Behebung:** Vom Nutzer gelieferte Feld- und Sortiernamen ausschließlich über eine feste Allowlist auf bekannte Spalten abbilden; niemals den Request-Wert direkt als Spaltenargument weiterreichen.
- **Archivquelle:** [Column Name SQL Injection Analyzer](https://web.archive.org/web/20251110221136/https://www.laravel-enlightn.com/docs/security/column-name-sql-injection-analyzer.html)

#### 27. Raw Queries SQL Injection

- **Historische Metadaten:** `Critical`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\RawSQLInjectionAnalyzer`.
- **Prüfziel:** Erkennt untrusted Eingaben, die durch String-Verkettung in Raw SQL, `whereRaw`, `fromRaw`, direkte `DB`-Statements oder `unprepared` gelangen.
- **Empfohlene Behebung:** Positions- oder Named Bindings verwenden und dynamische SQL-Fragmente auf kontrollierte Werte begrenzen. Vollständig vom Nutzer gelieferte Query-Strings sind zu entfernen.
- **Archivquelle:** [Raw SQL Injection Analyzer](https://web.archive.org/web/20240718080447/https://www.laravel-enlightn.com/docs/security/raw-sql-injection-analyzer.html)

#### 28. Native SQL Injection

- **Historische Metadaten:** `Critical`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\SqlInjectionAnalyzer`.
- **Prüfziel:** Markiert direkten PDO-Zugriff, native MySQL-/PostgreSQL-Funktionen und `DB::unprepared`, weil dabei Laravel-seitige Schutzmechanismen leichter umgangen werden können.
- **Historische Behebung:** Gefährliche native Aufrufe entfernen und Eloquent oder den Query Builder mit Bindings verwenden. Eine konfigurierbare Liste unsicherer SQL-Funktionen steuerte die Erkennung.
- **Modernisierungsbedarf:** Direkter Datenbankzugriff ist nicht automatisch verwundbar, wenn Prepared Statements korrekt eingesetzt werden. Eine LaraBeacon-Regel sollte Datenfluss und Bindings analysieren und nicht nur Funktionsnamen sperren.
- **Archivquelle:** [SQL Injection Analyzer](https://web.archive.org/web/20250716082303/https://www.laravel-enlightn.com/docs/security/sql-injection-analyzer.html)

#### 29. Validation Rule SQL Injection

- **Historische Metadaten:** `Critical`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\ValidationSQLInjectionAnalyzer`.
- **Prüfziel:** Erkennt Request-Werte, die an `Rule::unique(...)->ignore(...)` übergeben werden. Sowohl die ignorierte ID als auch ein dynamischer Spaltenname können bei unkontrollierter Herkunft zu SQL Injection führen.
- **Empfohlene Behebung:** Nur vertrauenswürdige Modellschlüssel beziehungsweise serverseitig ermittelte IDs verwenden und den Spaltennamen niemals aus dem Request übernehmen.
- **Archivquelle:** [Validation SQL Injection Analyzer](https://web.archive.org/web/20250916124822/https://www.laravel-enlightn.com/docs/security/validation-sql-injection-analyzer.html)

### Security Headers

#### 30. Clickjacking

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\ClickjackingAnalyzer`.
- **Prüfziel:** Prüft, ob Seiten gegen unerwünschte Einbettung in Frames geschützt sind. Historisch wurde dazu vor allem `X-Frame-Options` erwartet.
- **Empfohlene Behebung:** `X-Frame-Options: SAMEORIGIN` am Webserver oder in Middleware setzen. Eine moderne Implementierung sollte zusätzlich die CSP-Direktive `frame-ancestors` auswerten.
- **Ausnahme:** Anwendungen, die bewusst fremde Einbettung erlauben, benötigen eine gezielte Policy statt eines pauschalen Verbots.
- **Archivquelle:** [Clickjacking Analyzer](https://web.archive.org/web/20240911152219/https://www.laravel-enlightn.com/docs/security/clickjacking-analyzer.html)

#### 31. Mime Sniffing

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\MimeSniffingAnalyzer`.
- **Prüfziel:** Kontrolliert `X-Content-Type-Options`. Ohne `nosniff` können Browser hochgeladene oder falsch deklarierte Dateien als ausführbares HTML beziehungsweise Skript interpretieren.
- **Empfohlene Behebung:** `X-Content-Type-Options: nosniff` konsistent über Webserver, Proxy oder Anwendungs-Middleware setzen.
- **Modernisierungsbedarf:** Der historische Check übersprang zustandslose API-Anwendungen. Auch API- und Download-Antworten können jedoch von korrekten Typen und `nosniff` profitieren; diese Ausnahme sollte neu bewertet werden.
- **Archivquelle:** [Mime Sniffing Analyzer](https://web.archive.org/web/20250916140355/https://www.laravel-enlightn.com/docs/security/mime-sniffing-analyzer.html)

#### 32. Web Server Fingerprinting

- **Historische Metadaten:** `Minor`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\WebServerFingerprintingAnalyzer`.
- **Prüfziel:** Erkennt, ob der `Server`-Header Produkt und Versionsnummer des Webservers preisgibt. Die Information ist allein keine Schwachstelle, erleichtert aber das gezielte Suchen nach ungepatchten Versionen.
- **Empfohlene Behebung:** Versionen am CDN oder Reverse Proxy verbergen; für Nginx etwa `server_tokens off`, für Apache eine restriktive `ServerTokens`-Einstellung verwenden.
- **Einordnung:** Dies ist Defense in Depth und ersetzt weder Updates noch ein wirksames Patch-Management.
- **Archivquelle:** [Web Server Fingerprinting Analyzer](https://web.archive.org/web/20240228123217/https://www.laravel-enlightn.com/docs/security/web-server-fingerprinting-analyzer.html)

### Unrestricted File Uploads and DoS Attacks

#### 33. Arbitrary File Uploads

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\ArbitraryFileUploadAnalyzer`.
- **Prüfziel:** Findet Upload-Felder ohne Dateitypprüfung. Ohne Einschränkung könnten ausführbare oder anderweitig gefährliche Dateien hochgeladen und bei öffentlicher Erreichbarkeit ausgeführt werden.
- **Empfohlene Behebung:** Erlaubte MIME-Typen und Dateiendungen explizit validieren. Eine moderne Umsetzung sollte zusätzlich Dateiinhalte prüfen, zufällige Server-Dateinamen verwenden und Uploads möglichst außerhalb direkt ausführbarer Webpfade speichern.
- **Archivquelle:** [Arbitrary File Upload Analyzer](https://web.archive.org/web/20240418013117/https://www.laravel-enlightn.com/docs/security/arbitrary-file-upload-analyzer.html)

#### 34. Directory Traversal

- **Historische Metadaten:** `Critical`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\DirectoryTraversalAnalyzer`.
- **Prüfziel:** Erkennt untrusted Pfade in Dateioperationen wie Downloads, Reads, Copies und Storage-Zugriffen, über die Sequenzen wie `../` oder absolute Pfade aus dem erlaubten Verzeichnis ausbrechen könnten.
- **Empfohlene Behebung:** Bei festen Verzeichnissen nur den Basisnamen akzeptieren. Bei erlaubten Unterordnern den kanonischen Pfad ermitteln und mit einer echten Verzeichnisgrenze gegen den vorgesehenen Root prüfen; `realpath` allein bietet keinen Schutz.
- **Zusätzliche Härtung:** `doc_root` oder `open_basedir` können den erreichbaren Bereich einschränken, wobei `open_basedir` laut historischer Dokumentation den Realpath-Cache und damit die Performance beeinträchtigt.
- **Archivquelle:** [Directory Traversal Analyzer](https://web.archive.org/web/20251208020037/https://www.laravel-enlightn.com/docs/security/directory-traversal-analyzer.html)

#### 35. Regex DoS Attacks

- **Historische Metadaten:** `Critical`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\RegexDosAnalyzer`.
- **Prüfziel:** Sucht nach regulären Ausdrucksmustern, die direkt aus Nutzereingaben stammen und dadurch gezielt extrem teure Backtracking-Fälle auslösen können.
- **Empfohlene Behebung:** Nutzer dürfen keine frei ausführbaren Regex-Patterns bestimmen; stattdessen feste Muster oder streng begrenzte Suchoptionen anbieten.
- **Bekannte Grenze:** Der historische Analyzer erkannte keine bereits im Code fest eingebauten, algorithmisch problematischen Regex-Muster. Eine Neuentwicklung könnte hierfür eine ergänzende Pattern-Analyse vorsehen.
- **Archivquelle:** [Regex DOS Analyzer](https://web.archive.org/web/20241102105835/https://www.laravel-enlightn.com/docs/security/regex-dos-analyzer.html)

#### 36. Storage DoS Attacks

- **Historische Metadaten:** `Minor`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\FileSizeValidationAnalyzer`.
- **Prüfziel:** Findet Datei-Uploads ohne anwendungsspezifische Größenbeschränkung. Globale PHP-Limits reichen nicht aus, wenn unterschiedliche Upload-Felder unterschiedliche sinnvolle Maximalgrößen besitzen.
- **Empfohlene Behebung:** Pro Upload-Feld `max`, `size` oder `between` in Kilobyte festlegen und zusätzlich Infrastruktur-Limits, Benutzerquoten sowie verfügbaren Speicher berücksichtigen.
- **Archivquelle:** [File Size Validation Analyzer](https://web.archive.org/web/20240811153709/https://www.laravel-enlightn.com/docs/security/file-size-validation-analyzer.html)

#### 37. Unrestricted File Uploads

- **Historische Metadaten:** `Critical`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\UnrestrictedFileUploadAnalyzer`.
- **Prüfziel:** Erkennt Upload-Zielpfade, die aus Nutzereingaben zusammengesetzt werden. Angreifer könnten dadurch Dateien außerhalb des vorgesehenen Ordners ablegen oder vorhandene Dateien überschreiben.
- **Empfohlene Behebung:** Zielverzeichnis serverseitig festlegen, Dateinamen auf den Basisnamen reduzieren oder kanonische Pfade strikt gegen den erlaubten Root prüfen. `realpath` ohne anschließende Containment-Prüfung reicht nicht aus.
- **Zusätzliche Härtung:** Zufällige serverseitige Namen, nicht öffentliche Storage-Bereiche und minimale Dateisystemrechte reduzieren die Auswirkungen weiterer Fehler.
- **Archivquelle:** [Unrestricted File Upload Analyzer](https://web.archive.org/web/20251110223118/https://www.laravel-enlightn.com/docs/security/unrestricted-file-upload-analyzer.html)

#### 38. ZIP and XML File Bombs

- **Historische Metadaten:** `Minor`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\FileBombValidationAnalyzer`.
- **Prüfziel:** Warnt, sobald Upload-Validierungen ZIP- oder XML-Dateien erlauben. ZIP-Bomben können beim Entpacken Speicher und Datenträger füllen; unsichere XML-Verarbeitung ermöglicht unter anderem XXE- und Billion-Laughs-Angriffe.
- **Empfohlene Behebung:** Diese Formate vermeiden, wenn sie nicht zwingend benötigt werden. Andernfalls Entpackgröße, Dateianzahl und Rekursion begrenzen sowie XML-Parser ohne externe Entitäten und Netzwerkladevorgänge konfigurieren.
- **Bekannte Grenze:** Die historische Regel konnte die konkret eingesetzte Parser- oder Archivbibliothek nicht beurteilen und meldete deshalb jeden erlaubten ZIP-/XML-Typ als potenzielles Risiko.
- **Archivquelle:** [File Bomb Validation Analyzer](https://web.archive.org/web/20240418014358/https://www.laravel-enlightn.com/docs/security/file-bomb-validation-analyzer.html)

### Injection and Phishing Attacks

#### 39. Command Injection

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\CommandInjectionAnalyzer`.
- **Prüfziel:** Erkennt Nutzereingaben, die unescaped in Befehle für `exec`, `shell_exec`, `system`, `passthru` oder ähnliche Shell-Schnittstellen gelangen.
- **Historische Behebung:** Argumente mit `escapeshellarg` beziehungsweise vollständige Befehle mit `escapeshellcmd` absichern.
- **Modernisierungsbedarf:** Sicherer ist es, Shell-Interpretation vollständig zu vermeiden, Programme und Argumente getrennt an eine Process-API zu übergeben und erlaubte Werte zusätzlich zu validieren. Die spätere Datenflussanalyse sollte diese Varianten unterscheiden.
- **Archivquelle:** [Command Injection Analyzer](https://web.archive.org/web/20251110232422/https://www.laravel-enlightn.com/docs/security/command-injection-analyzer.html)

#### 40. Host Injection

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\HostInjectionAnalyzer`.
- **Prüfziel:** Prüft Manipulationen über `Host` und `X-Forwarded-Host`, die etwa absolute Links in Passwort-Reset-Mails, Redirects oder Caches auf eine fremde Domain lenken könnten.
- **Empfohlene Behebung:** Erlaubte Hosts mit `TrustHosts` festlegen, Trusted Proxies und akzeptierte Forwarded-Header eng konfigurieren, unbekannte Hostnamen am Webserver abweisen und am Reverse Proxy alle relevanten Forwarded-Header kontrolliert überschreiben.
- **Bekannte Grenze:** Der historische Analyzer erkannte nur einen Teil der dokumentierten sicheren Proxy-Varianten und wurde ohne Trusted Proxies übersprungen. LaraBeacon sollte Host- und Forwarded-Host-Schutz getrennt sowie möglichst anhand realer Responses prüfen.
- **Archivquelle:** [Host Injection Analyzer](https://web.archive.org/web/20250422150352/https://www.laravel-enlightn.com/docs/security/host-injection-analyzer.html)

#### 41. Object Injection

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\ObjectInjectionAnalyzer`.
- **Prüfziel:** Erkennt `unserialize()` auf untrusted Daten. Manipulierte PHP-Objekte können über Magic Methods je nach vorhandenen Klassen Dateien verändern, Codepfade auslösen oder weitere Injection- und DoS-Folgen verursachen.
- **Empfohlene Behebung:** Keine Nutzereingaben mit PHP-Deserialisierung verarbeiten; für reine Datenstrukturen ein Format wie JSON mit expliziter Schema- und Typvalidierung verwenden.
- **Archivquelle:** [Object Injection Analyzer](https://web.archive.org/web/20240418003239/https://www.laravel-enlightn.com/docs/security/object-injection-analyzer.html)

#### 42. Eval Code Injection

- **Historische Metadaten:** `Critical`, etwa 30 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\EvalAnalyzer`.
- **Prüfziel:** Meldet sämtliche `eval`-Verwendungen. Dynamisch ausgeführter PHP-Code erweitert die Angriffsfläche, erschwert statische Analyse und Debugging und kann Optimierungen durch Opcache beziehungsweise JIT verhindern.
- **Empfohlene Behebung:** `eval` vollständig entfernen und den Anwendungsfall durch reguläre Funktionen, Parser, Konfigurationsdaten oder eine klar begrenzte Ausdruckssprache ersetzen.
- **Archivquelle:** [Eval Analyzer](https://web.archive.org/web/20251208002413/https://www.laravel-enlightn.com/docs/security/eval-analyzer.html)

#### 43. Extract Variable Hijacking

- **Historische Metadaten:** `Critical`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\ExtractAnalyzer`.
- **Prüfziel:** Erkennt `extract()` mit untrusted Request-Daten. Array-Schlüssel werden dabei zu Variablennamen und können bestehende Werte beziehungsweise potenziell sicherheitsrelevante Namen überschreiben.
- **Historische Behebung:** Nur eine feste Teilmenge aus `only()` oder validierte Form-Request-Daten übergeben.
- **LaraBeacon-Empfehlung:** Auch mit gefilterten Daten ist explizite Variablenzuweisung verständlicher und sicherer. Eine neue Regel sollte `extract()` mit Request-Herkunft hoch priorisieren und weitere Verwendungen zumindest als Wartbarkeitsproblem melden.
- **Archivquelle:** [Extract Analyzer](https://web.archive.org/web/20251009100807/https://www.laravel-enlightn.com/docs/security/extract-analyzer.html)

#### 44. Open Redirection

- **Historische Metadaten:** `Critical`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\OpenRedirectionAnalyzer`.
- **Prüfziel:** Erkennt Redirect-Ziele aus Nutzereingaben. Vertrauenswürdig aussehende Links der eigenen Domain können sonst auf Phishing-Seiten weiterleiten.
- **Empfohlene Behebung:** Interne, benannte Routen bevorzugen. Falls externe Ziele fachlich nötig sind, erlaubte Hosts und Protokolle serverseitig validieren oder mit kurzlebigen, signierten Zielkennungen arbeiten.
- **Archivquelle:** [Open Redirection Analyzer](https://web.archive.org/web/20241005210555/https://www.laravel-enlightn.com/docs/security/open-redirection-analyzer.html)

### Dependency Management

#### 45. Horizon Security

- **Historische Metadaten:** `Minor`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\HorizonSecurityAnalyzer`.
- **Prüfziel:** Prüft, ob die Horizon-Oberfläche auf einer eigenen Subdomain läuft, damit ein Angriff auf die Hauptanwendung nicht ohne Weiteres auf dieselbe Browser-Origin und dieselben Cookies der Admin-Oberfläche übergreift.
- **Empfohlene Behebung:** Eine eigene `horizon`-Domain konfigurieren und die Session-Cookie-Domain auf `null` begrenzen, damit das Cookie nicht automatisch mit allen Subdomains geteilt wird.
- **Ausnahme und Einordnung:** Wird lokal bei aktivem `skip_env_specific` oder ohne Horizon übersprungen. Eine separate Subdomain ersetzt keine strenge Autorisierung, Netzwerkbegrenzung und sichere Cookie-Konfiguration.
- **Archivquelle:** [Horizon Security Analyzer](https://web.archive.org/web/20240718071917/https://www.laravel-enlightn.com/docs/security/horizon-security-analyzer.html)

#### 46. Telescope Security

- **Historische Metadaten:** `Minor`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\TelescopeSecurityAnalyzer`.
- **Prüfziel:** Erwartet für Telescope eine eigene Subdomain und getrennten Cookie-Scope, um sensible Diagnoseinformationen stärker von der Hauptanwendung zu isolieren.
- **Empfohlene Behebung:** `telescope.domain` auf eine separate Domain setzen, `session.domain` nicht auf die gemeinsame Parent-Domain erweitern und den Zugriff zusätzlich strikt autorisieren.
- **Ausnahme:** Wird lokal bei aktivem `skip_env_specific` oder ohne Telescope übersprungen.
- **Archivquelle:** [Telescope Security Analyzer](https://web.archive.org/web/20241102110245/https://www.laravel-enlightn.com/docs/security/telescope-security-analyzer.html)

#### 47. Nova Security

- **Historische Metadaten:** `Minor`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Security\NovaSecurityAnalyzer`.
- **Prüfziel:** Prüft die Isolation der Nova-Administrationsoberfläche auf einer eigenen Subdomain.
- **Empfohlene Behebung:** `nova.domain` separat konfigurieren und Session-Cookies auf den jeweiligen Host begrenzen. Ergänzend bleiben starke Authentifizierung, Autorisierung und gegebenenfalls Netzwerkzugriffsschutz erforderlich.
- **Ausnahme:** Wird lokal bei aktivem `skip_env_specific` oder ohne Nova übersprungen.
- **Archivquelle:** [Nova Security Analyzer](https://web.archive.org/web/20240718071147/https://www.laravel-enlightn.com/docs/security/nova-security-analyzer.html)

## Reliability (17)

### Health Checks

#### 48. Disk Space

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\DiskSpaceAnalyzer`.
- **Prüfziel:** Überwacht den belegten Speicher des Dateisystems. Historisch schlug der Check bei mehr als 90 Prozent Nutzung fehl; der Grenzwert war über `disk_usage_threshold` konfigurierbar.
- **Empfohlene Behebung:** Nicht benötigte Daten und Logs kontrolliert bereinigen, Speicher erweitern oder persistente Daten auf geeignete externe Systeme verlagern. Für Cloud- und Container-Umgebungen muss LaraBeacon das tatsächlich relevante Volume bestimmen.
- **Archivquelle:** [Disk Space Analyzer](https://web.archive.org/web/20240811151132/https://www.laravel-enlightn.com/docs/reliability/disk-space-analyzer.html)

#### 49. Horizon Status

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\HorizonStatusAnalyzer`.
- **Prüfziel:** Bestätigt, dass Horizon für eine Anwendung mit Horizon tatsächlich läuft.
- **Empfohlene Behebung:** Den von Supervisor oder einem anderen Process Manager verwalteten Horizon-Prozess starten beziehungsweise die Prozesskonfiguration reparieren. Lokal kann `php artisan horizon` im Vordergrund dienen.
- **Ausnahme:** Wird ohne installierte beziehungsweise verwendete Horizon-Komponente übersprungen.
- **Archivquelle:** [Horizon Status Analyzer](https://web.archive.org/web/20250808020813/https://www.laravel-enlightn.com/docs/reliability/horizon-status-analyzer.html)

#### 50. Redis Status

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\RedisStatusAnalyzer`.
- **Prüfziel:** Testet die Erreichbarkeit der konfigurierten Redis-Verbindungen. Historisch wurden standardmäßig alle Verbindungen geprüft; `redis_connections` konnte die Auswahl begrenzen.
- **Empfohlene Behebung:** Redis-Prozess, DNS beziehungsweise Socket, Netzwerkregeln, TLS und Zugangsdaten kontrollieren. LaraBeacon sollte pro Verbindung klar ausweisen, welcher Verbindungsaufbau scheitert.
- **Ausnahme:** Wird bei Anwendungen ohne Redis übersprungen.
- **Archivquelle:** [Redis Status Analyzer](https://web.archive.org/web/20240811152436/https://www.laravel-enlightn.com/docs/reliability/redis-status-analyzer.html)

#### 51. Storage Links

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\StorageLinkAnalyzer`.
- **Prüfziel:** Kontrolliert, ob die in Laravel konfigurierten öffentlichen Storage-Symlinks existieren. Fehlende Links verhindern die Auslieferung von Dateien des `public`-Disks und weiterer konfigurierter Links.
- **Empfohlene Behebung:** `php artisan storage:link` als Deployment-Schritt ausführen und Ziel, Berechtigungen sowie Container-/Release-Pfade prüfen.
- **Archivquelle:** [Storage Link Analyzer](https://web.archive.org/web/20240911162559/https://www.laravel-enlightn.com/docs/reliability/storage-link-analyzer.html)

### Detecting Misconfigurations

#### 52. Failed Job Timeouts

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\FailedJobTimeoutAnalyzer`.
- **Prüfziel:** Wertet fehlgeschlagene Queue-Jobs aus und identifiziert Klassen, deren Verarbeitung wegen eines Timeouts abgebrochen wurde.
- **Empfohlene Behebung:** Ursache und Laufzeitprofil prüfen, große Jobs in kleinere idempotente Einheiten aufteilen oder – wenn die Laufzeit fachlich erforderlich ist – das Job-Timeout kontrolliert erhöhen. Worker-Timeout und `retry_after` müssen weiterhin zueinander passen.
- **Ausnahme:** Wird bei `sync`- und `null`-Queue-Treibern übersprungen.
- **Archivquelle:** [Failed Job Timeout Analyzer](https://web.archive.org/web/20240620033632/https://www.laravel-enlightn.com/docs/reliability/failed-job-timeout-analyzer.html)

#### 53. Horizon Prefix

- **Historische Metadaten:** `Major`, etwa 1 Minute; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\HorizonPrefixAnalyzer`.
- **Prüfziel:** Warnt vor dem allgemeinen Standardpräfix `laravel_horizon:`, wenn mehrere Anwendungen denselben Redis-Server teilen. Kollisionen können Dashboard-Daten, Metriken und Supervisor-Zustände vermischen.
- **Empfohlene Behebung:** Ein anwendungsspezifisches `HORIZON_PREFIX` setzen und bei gemeinsamem Redis zusätzlich eine separate Horizon-Datenbank beziehungsweise eine anderweitig isolierte Verbindung verwenden.
- **Ausnahme:** Wird ohne Horizon übersprungen.
- **Archivquelle:** [Horizon Prefix Analyzer](https://web.archive.org/web/20240811150647/https://www.laravel-enlightn.com/docs/reliability/horizon-prefix-analyzer.html)

#### 54. Horizon Provisioning Plans

- **Historische Metadaten:** `Critical`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\HorizonProvisioningPlanAnalyzer`.
- **Prüfziel:** Stellt sicher, dass `horizon.environments` einen Provisionierungsplan für die aktuell laufende Laravel-Umgebung enthält. Ohne passenden Eintrag startet Horizon dort keine Supervisor-Konfiguration.
- **Empfohlene Behebung:** Den exakten Environment-Namen mit sinnvollen Prozess-, Balance- und Cooldown-Werten in `config/horizon.php` ergänzen.
- **Ausnahme:** Wird ohne Horizon übersprungen.
- **Archivquelle:** [Horizon Provisioning Plan Analyzer](https://web.archive.org/web/20240718084655/https://www.laravel-enlightn.com/docs/reliability/horizon-provisioning-plan-analyzer.html)

#### 55. Queue Blocking

- **Historische Metadaten:** `Major`, etwa 1 Minute; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\QueueBlockingAnalyzer`.
- **Prüfziel:** Beanstandet `block_for = 0` beim Redis-Queue-Treiber. Ein Worker blockiert dann unbegrenzt bis zum nächsten Job und verarbeitet Signale wie `SIGTERM` möglicherweise erst danach.
- **Empfohlene Behebung:** `block_for` auf `null` oder einen positiven, zur Betriebsstrategie passenden Wert setzen, damit Worker regelmäßig auf Prozesssignale reagieren können.
- **Ausnahme:** Wird ohne Redis-Queue übersprungen.
- **Archivquelle:** [Queue Blocking Analyzer](https://web.archive.org/web/20240718071026/https://www.laravel-enlightn.com/docs/reliability/queue-blocking-analyzer.html)

#### 56. PCNTL

- **Historische Metadaten:** `Major`, etwa 2 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\PcntlAnalyzer`.
- **Prüfziel:** Kontrolliert, ob die PHP-Erweiterung PCNTL für Queue-Job-Timeouts verfügbar ist. Ohne Signalunterstützung können konfigurierte Job-Timeouts wirkungslos bleiben.
- **Empfohlene Behebung:** PCNTL in der PHP-Laufzeit installieren beziehungsweise aktivieren, die tatsächlich die Queue-Worker ausführt.
- **Ausnahme und Modernisierungsbedarf:** Wird bei `sync` und `null` übersprungen. In Container- und Multi-SAPI-Setups muss die Worker-CLI geprüft werden, nicht lediglich die Webserver-PHP-Konfiguration.
- **Archivquelle:** [PCNTL Analyzer](https://web.archive.org/web/20250916142353/https://www.laravel-enlightn.com/docs/reliability/pcntl-analyzer.html)

#### 57. Redis Eviction Policy

- **Historische Metadaten:** `Major`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\RedisEvictionPolicyAnalyzer`.
- **Prüfziel:** Prüft, ob die Redis-Verdrängungsstrategie zur Verwendung der jeweiligen Datenbank passt. Für persistente Queue- oder Session-Daten wurde `noeviction` erwartet; für eine reine Cache-Datenbank empfahl Enlightn `allkeys-lfu` ab Redis 4 beziehungsweise `allkeys-lru` ab Redis 3.
- **Empfohlene Behebung:** `maxmemory-policy` in der Redis-Konfiguration passend setzen und Redis kontrolliert neu starten. Cache und persistente Daten sollten getrennte Verbindungen beziehungsweise Instanzen verwenden, wenn sie unterschiedliche Garantien benötigen.
- **Ausnahme und Modernisierungsbedarf:** Wurde lokal bei aktivem `skip_env_specific` oder ohne Redis übersprungen. LaraBeacon sollte jede tatsächlich verwendete Verbindung einzeln bewerten und bei verwalteten Redis-Diensten einen nicht auslesbaren Policy-Status ausdrücklich als unbekannt behandeln.
- **Archivquelle:** [Redis Eviction Policy Analyzer](https://web.archive.org/web/20251208015736/https://www.laravel-enlightn.com/docs/reliability/redis-eviction-policy-analyzer.html)

#### 58. Redis Prefix

- **Historische Metadaten:** `Major`, etwa 1 Minute; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\RedisPrefixAnalyzer`.
- **Prüfziel:** Erkennt das allgemeine Laravel-Standardpräfix `laravel_database_`, das bei mehreren Anwendungen auf demselben Redis-Server zu Schlüsselkollisionen führen kann.
- **Empfohlene Behebung:** Über `REDIS_PREFIX` oder `config/database.php` ein eindeutiges, anwendungsspezifisches Präfix setzen. Bei gemeinsam genutzten Servern empfiehlt sich zusätzlich eine getrennte Redis-Datenbank oder Verbindung.
- **Ausnahme:** Wird übersprungen, wenn die Anwendung Redis nicht verwendet.
- **Archivquelle:** [Redis Prefix Analyzer](https://web.archive.org/web/20240718065448/https://www.laravel-enlightn.com/docs/reliability/redis-prefix-analyzer.html)

#### 59. Redis Shared Database

- **Historische Metadaten:** `Major`, etwa 2 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\RedisSharedDatabaseAnalyzer`.
- **Prüfziel:** Erkennt, wenn der Redis-Cache dieselbe Datenbank wie persistente Dienste – etwa Queues, Sessions oder Broadcasting – verwendet. Ein `cache:clear` könnte dadurch auch Jobs, Sitzungen oder Nachrichten entfernen.
- **Empfohlene Behebung:** Cache und persistente Dienste in ihren jeweiligen Konfigurationen unterschiedlichen Redis-Verbindungen und Datenbanknummern zuordnen, beispielsweise über getrennte Werte für `REDIS_DB` und `REDIS_CACHE_DB`.
- **Ausnahme und Modernisierungsbedarf:** Der historische Analyzer lief nur, wenn Redis der Standard-Cache-Store war. LaraBeacon sollte sämtliche konfigurierten Cache-Stores, Queue-Verbindungen, Session-Treiber und Broadcasting-Verbindungen vergleichen, damit benannte oder sekundäre Stores nicht übersehen werden.
- **Archivquelle:** [Redis Shared Database Analyzer](https://web.archive.org/web/20251110220338/https://www.laravel-enlightn.com/docs/reliability/redis-shared-database-analyzer.html)

### Dead Routes and Dead Code

#### 60. Dead Routes

- **Historische Metadaten:** `Major`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\DeadRouteAnalyzer`.
- **Prüfziel:** Findet registrierte Routen mit nicht auflösbaren Controllern, nicht vorhandenen Action-Methoden oder nicht öffentlich aufrufbaren Methoden.
- **Empfohlene Behebung:** Nicht mehr benötigte Routen entfernen oder Controllerreferenz, Methode und Sichtbarkeit korrigieren. Der historische Report zeigte dazu URI und HTTP-Methoden jeder betroffenen Route an.
- **Modernisierungsbedarf:** Eine LaraBeacon-Implementierung muss aktuelle Laravel-Routenformen wie invokable Controller, Closures, FQCN-Actions und gecachte Routen korrekt behandeln, ohne dynamisch registrierte gültige Routen fälschlich zu melden.
- **Archivquelle:** [Dead Route Analyzer](https://web.archive.org/web/20250916124350/https://www.laravel-enlightn.com/docs/reliability/dead-route-analyzer.html)

### Good Practices

#### 61. Use Cache Busting

- **Historische Metadaten:** `Major`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\CacheBustingAnalyzer`.
- **Prüfziel:** Bestätigt, dass veröffentlichte Frontend-Assets eine versionsabhängige URL erhalten, damit Browser nach einem Deployment keine veralteten JavaScript- oder CSS-Dateien ausliefern.
- **Historische Behebung:** In Laravel Mix `.version()` aktivieren und Assets in Blade über den `mix()`-Helper referenzieren.
- **Modernisierungsbedarf:** Laravel Mix ist für neue Laravel-Anwendungen durch Vite abgelöst. LaraBeacon sollte deshalb primär Vites gehashte Manifest-Assets und `@vite` prüfen, Mix aber für ältere Projekte weiterhin erkennen. Eigene Bundler oder CDN-Pipelines benötigen eine konfigurierbare Ausnahme.
- **Ausnahme:** Wurde lokal bei aktivem `skip_env_specific` oder ohne Laravel Mix übersprungen.
- **Archivquelle:** [Cache Busting Analyzer](https://web.archive.org/web/20251110230538/https://www.laravel-enlightn.com/docs/reliability/cache-busting-analyzer.html)

#### 62. Setup Composer Scripts for Publishing Vendor Assets

- **Historische Metadaten:** `Minor`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\ComposerPackagePublishAnalyzer`.
- **Prüfziel:** Prüft, ob Assets von Paketen wie Horizon, Telescope oder Nova nach Paketupdates reproduzierbar neu veröffentlicht werden. Veraltete veröffentlichte Assets können sonst nicht zum installierten PHP-Paket passen.
- **Empfohlene Behebung:** Den vom jeweiligen Paket vorgesehenen Publish-Befehl in `composer.json` unter `post-update-cmd` hinterlegen, historisch beispielsweise `@php artisan horizon:publish --ansi`.
- **Modernisierungsbedarf:** LaraBeacon sollte nur Pakete melden, die in der installierten Version tatsächlich einen manuellen Publish-Schritt benötigen, und vorhandene Framework- beziehungsweise Paket-Skripte berücksichtigen. In Produktion bleibt ein Deployment aus dem Lockfile maßgeblich; `composer update` gehört nicht auf den Server.
- **Archivquelle:** [Composer Package Publish Analyzer](https://web.archive.org/web/20241005215142/https://www.laravel-enlightn.com/docs/reliability/composer-package-publish-analyzer.html)

#### 63. Don't Use Script Terminating Functions

- **Historische Metadaten:** `Minor`, etwa 5 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\ScriptTerminatingFunctionAnalyzer`.
- **Prüfziel:** Erkennt direkte Skriptabbrüche wie `exit` und `die`. Sie umgehen den regulären Kontrollfluss und können Framework-Abschlusslogik, Middleware, Tests oder aufrufenden Code unerwartet abschneiden.
- **Empfohlene Behebung:** In Artisan-Commands einen Exit-Code zurückgeben; in HTTP-Code Responses, Exceptions oder Laravels `abort()`-Helper verwenden. Die historische Blacklist war über `terminating_function_blacklist` konfigurierbar.
- **Modernisierungsbedarf:** Neben Funktionsaufrufen muss die Regel die PHP-Sprachkonstrukte `exit` und `die` in allen syntaktischen Formen zuverlässig über den AST erkennen und erlaubte Grenzfälle konfigurierbar machen.
- **Archivquelle:** [Script Terminating Function Analyzer](https://web.archive.org/web/20240718075221/https://www.laravel-enlightn.com/docs/reliability/script-terminating-function-analyzer.html)

#### 64. Don't Use Globals or SuperGlobals

- **Historische Metadaten:** `Minor`, etwa 10 Minuten; Klasse `Enlightn\EnlightnPro\Analyzers\Reliability\GlobalVariableAnalyzer`.
- **Prüfziel:** Findet `global`-Deklarationen, direkte Zugriffe auf PHP-Superglobals wie `$_GET`, `$_COOKIE` oder `$_ENV` sowie native Funktionen wie `setcookie()` und `getenv()`. Solche Zugriffe umgehen Laravel-Abstraktionen, etwa Cookie-Verschlüsselung und die zentrale Request- oder Konfigurationsverarbeitung.
- **Empfohlene Behebung:** Abhängigkeiten über Klassen und den Service Container modellieren; Request-, Cookie-, Session-, Header- und Konfigurationsdaten über die vorgesehenen Laravel-APIs lesen und schreiben. Historisch waren Variablen- und Funktionsblacklists über `global_variable_blacklist` und `global_function_blacklist` anpassbar.
- **Modernisierungsbedarf:** LaraBeacon sollte echte problematische Laufzeitzugriffe von legitimen Framework-Grenzen, Tests und Polyfills unterscheiden. Die Formulierung „keine globalen Funktionen“ darf nicht pauschal alle global definierten Hilfsfunktionen erfassen, sondern nur die konfigurierten riskanten APIs.
- **Archivquelle:** [Global Variable Analyzer](https://web.archive.org/web/20240527211138/https://www.laravel-enlightn.com/docs/reliability/global-variable-analyzer.html)
