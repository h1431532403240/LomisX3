# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer YOUR_AUTH_TOKEN_HERE"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

使用 Laravel Sanctum Bearer Token 進行認證。請在請求頭中包含：Authorization: Bearer {token}
