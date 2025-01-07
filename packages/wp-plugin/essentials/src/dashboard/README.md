# Dashboard feature

This is a wordpress plugin that provides a dashboard for our customers.
The dashboards can be created and edited using gutenberg.

### show dashboard

http://localhost:8888/wp-admin/admin.php?page=custom-page

### edit dashboard

http://localhost:8888/wp-admin/edit.php?post_type=custom_dashboard
custom-ionos-dashboard -> edit

## concepts

### code split

The plugin code is split into two parts resembling the two features above.
To be able to edit and save the dashboard(s), the constant EDITOR_ENABLED has to be set.
This includes "ionos-dashboard-page-edit.php".

For simply being able to view the dashboard (our MVP customer use case), the main plugin file is enough.

### custom post type

When editing a dashboard, it is a post of a custom post type and can be edited with any _block theme_ active.
To get a clean dashboard with just the content, our own Template is used: "custom_dashboard/"block-template.php".

### dashboard files

The dashboards can be found in the "custom_dashboard/" dir in which there will be one dir for each dashboard. They are initially read from here and also saved to the dir.

Every dashboard consists of at least two files:

- "post_content.html" for the gutenberg post content
- "rendered-skeleton.html" which is the fully rendered html and styles of the page without the post content.

### saving

These files are written when saving a dashboard in the gutenberg editor. The first one simply contains the gutenberg post content.
To retrieve the rendered-skeleton, the post is first saved to the database. Afterwards we make a get request to the published custom post_type to get the fully theme-rendered html and strip out the content part.

### rendering

When viewing a dashboard, the two parts can be put together like this:

- the rendered-skeleton.html can be used as is
- the post_content can contain dynamic blocks which need to be server side rendered. Therefore "do_blocks()" is called for the post_content. The output is inserted into the rendered skeleton.

This allows the user to have any theme active and customize it without having an effect on the look of the dashboard.

### example dynamic block

As a proof of the concept, a simple "clock-block" is used on the dashboard to show that the server side rendering works.
