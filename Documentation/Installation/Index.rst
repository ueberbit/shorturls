.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

The extension is available on
`Packagist <https://packagist.org/packages/ueberbit/shorturls>`__ and in the
`TYPO3 Extension Repository (TER) <https://extensions.typo3.org/extension/shorturls>`__.

..  _installation-composer:

Composer mode
=============

..  code-block:: bash

    composer require ueberbit/shorturls

In Composer mode, the extension is loaded and active automatically once it
is required – there is no separate activation step in the
:guilabel:`Admin Tools > Extensions` backend module.

..  _installation-classic:

Classic mode
============

Download the extension from the TER, or search for ``shorturls`` in the
:guilabel:`Admin Tools > Extensions` backend module, and activate it there.

..  _installation-setup:

Setup
=====

The extension does not add any database tables; it reuses the
``sys_redirect`` table provided by ``typo3/cms-redirects``. All behaviour is
configured per site, see :ref:`configuration`.

..  _installation-local-development:

Local development
=================

To work on the extension inside a TYPO3 project, clone the repository (e.g.
into :file:`packages/shorturls`) and wire it in via a Composer ``path``
repository in the project's root :file:`composer.json`:

..  code-block:: json

    {
        "repositories": [
            {
                "type": "path",
                "url": "packages/shorturls"
            }
        ],
        "require": {
            "ueberbit/shorturls": "@dev"
        }
    }

The extension is then symlinked into :file:`vendor/`, so code changes take
effect immediately. If you change the extension's own :file:`composer.json`
(e.g. its autoloading or dependencies), run:

..  code-block:: bash

    composer update ueberbit/shorturls
