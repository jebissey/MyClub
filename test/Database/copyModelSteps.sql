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
    Step + 10000,
    Method,
    Uri,
    JsonGetParameters,
    JsonPostParameters,
    JsonConnectedUser,
    ExpectedResponseCode,
    Query,
    QueryExpectedResponse
FROM Test
WHERE Step BETWEEN 1000 AND 2400;

