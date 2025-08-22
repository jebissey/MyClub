WITH RenumberedSteps AS (
    SELECT "Id",
           1000 + (ROW_NUMBER() OVER (ORDER BY "Step") - 1) * 10 AS "NewStep"
    FROM "Test"
    WHERE "Step" IS NOT NULL
)
UPDATE "Test" 
SET "Step" = RenumberedSteps."NewStep"
FROM RenumberedSteps
WHERE "Test"."Id" = RenumberedSteps."Id";
