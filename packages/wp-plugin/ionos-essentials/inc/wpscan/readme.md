# WPScan

WPScan shows, if there are any vulnerabilities in the installed plugins or themes.
The database https://wpscan.com/ is used, via a middleware server of ours.

There are two types of isses:
- High ("Critical") (CVSS >= 7 )
- Medium ("Warning") (CVSS < 7)

## Plugins
For every issue, there is exactly one recommendend action (a button)
```mermaid
flowchart TD
  A[Plugins] --> B{Update Available?}
  B -- Yes --> F['Update' Button]
  B -- No --> C{Plugin Status}
  C -- Active --> D['Deactivate' Button]
  C -- Inactive --> E[No Button]

  classDef greenNode fill:#a3e635,stroke:#4d7c0f,stroke-width:2px,color:#000;
  class F greenNode;

  classDef redNode fill:#f87171,stroke:#b91c1c,stroke-width:2px,color:#000;
  class D redNode;
```
