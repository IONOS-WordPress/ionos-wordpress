# extendify

Extendify is a third party plugin with features like

- ai-onboarding
- ai tools when editing pages
- ai agent chat (available when viewing the published page logged in)
- ...

## setup

to have extendify working, the open source plugin from the marketplace, one "Site Assistant" Plugin (see _license plugin_ below) and the extendable theme from the marketplace.

## license plugin

the current setup features a license plugin on every instance.
the hope api annex "tenant_extendify" supports the values "full" and "onboarding".
there are 7 tenants with 2 licenses each, resulting in 14 different plugins. the plugin is called "Site Assistant" and the slug is e.g.

### feature set

the license is sent to a extendify server for evaluating the actual featureset behind the license.
this call is IP range restricted so it is only allowed from stretch instances.
currently _all tenants but Ionos_ are restricted
