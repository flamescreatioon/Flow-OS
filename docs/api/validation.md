# Validation

Use Form Requests and validated input, not controller-scattered checks or blind model assignment. Validation failures are 422 with `details.fields` mapping field names to message arrays. Omitted PATCH fields are unchanged; explicit null is validated as a requested null.
