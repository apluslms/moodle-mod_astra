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

Bootstrap Javascript is only used as an AMD module under the directory
stratumtwo/amd. The Bootstrap JS code is wrapped in an AMD module definition
there. There is one hack to make dropdowns work in Moodle on line 318 in
stratumtwo/amd/src/twbootstrap.js.
