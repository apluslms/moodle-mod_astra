#!/bin/sh

# make a tar archive of the directory that is installed in Moodle

# git archive includes only files stored in git, which is not good with the secret key file
#git archive -o mod_astra.tar.gz master astra/

tar -czf mod_astra.tgz --exclude='astra/classes/output/test_page.php' \
  --exclude='astra/templates/test_page.mustache' \
  --exclude='astra/templates/whole_test_page.mustache' \
  --exclude='astra/testpage.php' \
  astra/
