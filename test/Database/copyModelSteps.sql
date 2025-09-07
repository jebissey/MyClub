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
WHERE Step BETWEEN 2410 AND 4030;

