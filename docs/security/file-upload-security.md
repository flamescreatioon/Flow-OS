# File Upload Security

Uploads are not implemented. Future uploads require authorization and tenant ownership checks, explicit size/type/extension validation, content inspection where appropriate, generated internal IDs/filenames, collision-safe storage, and controlled serving.

Never trust a filename, extension, MIME claim, or path. Do not use user filenames as filesystem paths; block traversal such as `../`. Untrusted uploads must not be placed in executable public paths.
