ALTER PROCEDURE dbo.pr_performance_trace (@description VARCHAR(MAX), @request VARCHAR(MAX), @post VARCHAR(MAX))

AS

INSERT INTO performance_trace([description],request, post)
SELECT @description,@request,@post