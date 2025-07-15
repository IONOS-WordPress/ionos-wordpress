# WPScan

WPScan identifies any vulnerabilities present in your installed plugins or themes.
Vulnerability data is sourced from https://wpscan.com/ through our own middleware server.

There are two types of isses:
- High ("Critical") (CVSS >= 7 )
- Medium ("Warning") (CVSS < 7)

## Plugins
For every issue, there is exactly one recommendend action. "Show update information" is shown if update is available.
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

## Themes
For every issue, there is exactly one recommendend action. No "Show update information" is shown, even if update is available.
It is not considered, if a theme is active or not. Even an active theme is beeing deleted.
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

## Proposal
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

  B -- No --> D{Theme status}
  D -- inactive --> X['Delete' Button]
  D -- active -->E{other themes installed?}
  E -- only this --> H[install TwentyTwentyFive ]
  E -- more themes --> G[activate another theme]
  H --> G
  G --> X

  classDef greenNode fill:#a3e635,stroke-width:0px,color:#000;
  class F greenNode;

  classDef blueNode fill:#38bdf8,stroke-width:0px,color:#000;
  class G blueNode;
  class H blueNode

  classDef redNode fill:#f87171,stroke-width:0px,color:#000;
  class X redNode;
```
Green and red buttons are shown to the user, blue nodes happen secretly in the background without any further ado by the user.
