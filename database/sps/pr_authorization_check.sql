--pr_authorization_check 3418, 668
go

ALTER PROCEDURE dbo.pr_authorization_check (@id_user INT
                                            ,@id_programa INT)

AS

SELECT 1 allowed
FROM CI_MIDDLEWAY..MW_PROGRAMA P
INNER JOIN CI_MIDDLEWAY..MW_USUARIO_PROGRAMA UP ON UP.ID_PROGRAMA = P.ID_PROGRAMA
INNER JOIN CI_MIDDLEWAY..MW_USUARIO U ON U.ID_USUARIO = UP.ID_USUARIO
WHERE U.ID_USUARIO = @id_user AND P.ID_PROGRAMA = @id_programa