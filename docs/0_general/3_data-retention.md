---
title: Data Retention
---

## Disable saving submissions

You can disable saving submissions as content pages globally using the `storeSubmissions` option in `config.php` or on the Settings tab inside the Form editor in the panel.

Although there can be a few disadvantages to this, such as:

- File uploads are not supported

Additionally, please check with the author of a third-party integration to see if they rely on the submission data being saved if you're using such.

However, this does not disable saving submissions in the session data. If it's important for your case that there are no traces of the submission data on a server, you need to use the `api` submission mode and also disable [multi-step forms](./multi-step).
