.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

This extension is developed in-house for this project as a local package and
is not published on Packagist or the TYPO3 Extension Repository (TER). It is
wired into the project via a Composer ``path`` repository, configured in the
root :file:`composer.json`:

..  code-block:: json

    {
        "repositories": [
            {
                "type": "path",
                "url": "packages/shorturls"
            }
        ],
        "require": {
            "ueberbit/shorturls": "*"
        }
    }

Because this project runs in Composer mode, the extension is loaded and
active automatically once it is required – there is no separate activation
step in the :guilabel:`Admin Tools > Extensions` backend module.

..  _installation-update:

Updating after changes
=======================

Since the extension is symlinked into :file:`vendor/` via the path
repository, code changes take effect immediately. If you change
:file:`composer.json` of the extension itself (e.g. its autoloading or
dependencies), run:

..  code-block:: bash

    composer update ueberbit/shorturls
