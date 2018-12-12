ALTER PROCEDURE dbo.pr_partner_base_add (@id_partner UNIQUEIDENTIFIER
                                            ,@id_base INT)

AS

SET NOCOUNT ON;

DECLARE @id UNIQUEIDENTIFIER = NULL
        ,@deleted BIT = 0

SELECT @id=pdb.id
FROM CI_MIDDLEWAY..partner_database pdb
WHERE id_partner=@id_partner AND id_base=@id_base

IF @id IS NULL
BEGIN
    INSERT INTO CI_MIDDLEWAY..partner_database (id_partner,id_base,allEvent)
        SELECT @id_partner,@id_base,1
END
ELSE
BEGIN
    DELETE FROM CI_MIDDLEWAY..partner_database WHERE id=@id
    SET @deleted=1;
END

SELECT 1 success
        ,(CASE WHEN @deleted = 1 THEN 'Removido com sucesso' ELSE 'Adicionado com sucesso.' END) msg