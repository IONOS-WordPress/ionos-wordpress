# WPScan

WPScan detects vulnerabilities in your installed plugins and themes.
Vulnerability data is sourced from [wpscan.com](https://wpscan.com/) via our middleware server.

There are two types of issues:
- High ("Critical") (CVSS ≥ 7)
- Medium ("Warning") (CVSS < 7)

Both types are handled similarly but are listed under different headings.


### Security Plugin (legacy workflow)
### Plugins
For each issue, there is exactly one recommended action. If an update is available, a "Show update information" option is displayed.
```mermaid
flowchart TD
  A[Plugin] --> B{Update Available?}
  B -- Yes --> F['Update' Button]
  B -- No --> C{Plugin Status}
  C -- Active --> D['Deactivate' Button]
  C -- Inactive --> E[No Button]

  classDef greenNode fill:#a3e635,stroke-width:0px,color:#000;
  class F greenNode;

  classDef redNode fill:#f87171,stroke-width:0px,color:#000;
  class D redNode;
```

### Themes
For each issue, there is exactly one recommended action. "Show update information" is not displayed, even if an update is available.
The theme's active status is not considered—active themes can also be deleted.
```mermaid
flowchart TD
  A[Theme] --> B{Update Available?}
  B -- Yes --> F['Update' Button]
  B -- No --> D['Delete' Button]

  classDef greenNode fill:#a3e635,stroke-width:0px,color:#000;
  class F greenNode;

  classDef redNode fill:#f87171,stroke-width:0px,color:#000;
  class D redNode;
```

## Essentials Plugin
### Plugins
```mermaid
flowchart TD
  A[Plugin] --> B{Update Available?}
  B -- Yes --> F['Update' Button]
  B -- No --> D['Delete' Button]

  classDef greenNode fill:#a3e635,stroke-width:0px,color:#000;
  class F greenNode;

  classDef redNode fill:#f87171,stroke-width:0px,color:#000;
  class D redNode;
```

### Themes
```mermaid
flowchart TD
  A[Theme] --> B{Update available?}
  B -- yes --> F['Update' Button]

  B -- No --> D{Theme active?}
  D -- inactive --> X['Delete' Button]
  D -- active -->E[install and activate another theme ]


  classDef greenNode fill:#a3e635,stroke-width:0px,color:#000;
  class F greenNode;

  classDef blueNode fill:#38bdf8,stroke-width:0px,color:#000;
  class G blueNode;
  class H blueNode

  classDef redNode fill:#f87171,stroke-width:0px,color:#000;
  class X redNode;
```

