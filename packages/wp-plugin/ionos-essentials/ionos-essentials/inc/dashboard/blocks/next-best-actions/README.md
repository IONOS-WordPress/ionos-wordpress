# Next Best Actions (NBA)

## How to add new NBAs
All NBAs are defined in _config.php_. See there for examples.

There are four categories, each NBA must be at least in one and can be in multiple categories.

- setup-ai
- setup-noai
- always
- after-setup

If setup was not completed, one of the two _setup_-categories and _always_ are shown. After setup was completetd, _after-setup_ is shown.

## Views
There is a main-view in _views/main.php_, where all starts. In views, there is as less logic as possible. Logic comes from _index.php_, which is the controller.
