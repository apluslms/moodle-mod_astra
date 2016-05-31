#!/bin/sh

# make a tar archive of the directory that is installed in Moodle

# git archive includes only files stored in git, which is not good with the secret key file
#git archive -o mod_stratumtwo.tar.gz master stratumtwo/

tar -czf mod_stratumtwo.tgz --exclude='stratumtwo/classes/output/test_page.php' \
  --exclude='stratumtwo/templates/test_page.mustache' \
  --exclude='stratumtwo/templates/whole_test_page.mustache' \
  --exclude='stratumtwo/testpage.php' \
  stratumtwo/
