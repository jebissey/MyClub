INSERT INTO Test (
    Step,
    Method,
    Uri,
    JsonGetParameters,
    JsonPostParameters,
    JsonConnectedUser,
    ExpectedResponseCode,
    Query,
    QueryExpectedResponse
)
SELECT
    Step + 100000,
    Method,
    Uri,
    JsonGetParameters,
    JsonPostParameters,
    JsonConnectedUser,
    ExpectedResponseCode,
    Query,
    QueryExpectedResponse
FROM Test
WHERE Step BETWEEN 9340 AND 10990;

