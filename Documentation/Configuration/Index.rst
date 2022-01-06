.. include:: ../Includes.txt

.. _configuration:

=============
Configuration
=============

Checks can't be deactivated for each of distinct services
===============

.. code-block:: typoscript
    plugin.tx_lbohealth {
        settings {
            checks {
                mysql = 0
                redis = 0
                solr = 0
            }
        }
    }
