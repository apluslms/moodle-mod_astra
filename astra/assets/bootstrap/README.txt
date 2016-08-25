Bootstrap v3.3.6 HTML/CSS/JS framework
with the components that are listed in config.json. 
Not all components are included in order to avoid conflicts with the 
Moodle core CSS and themes. (Moodle core includes themes that are based
the old Bootstrap version 2.)

NOTICE that the Bootstrap container classes have been renamed: 
container to bs3-container and
container-fluid to bs3-container-fluid
(because Moodle uses its own container class for styling and most pages 
already use that container)

Bootstrap3 Javascript is not used. Instead, Bootstrap2 JS is used from
the theme_bootstrapbase (Moodle core theme). The theme includes the Bootstrap2
AMD module automatically in every page footer.
