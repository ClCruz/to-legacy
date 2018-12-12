-- select * from CI_MIDDLEWAY..mw_evento_extrainfo where id_evento=22657

ALTER PROCEDURE dbo.pr_admin_event_save (@api VARCHAR(100), @id_evento INT
        ,@description VARCHAR(MAX)
        ,@address VARCHAR(1000))

AS 


-- DECLARE @id_evento INT = 22666
--         ,@description VARCHAR(MAX) = NULL
--         ,@uri VARCHAR(1000) = NULL
--         ,@address VARCHAR(1000) = 'Acquaplay'


SET NOCOUNT ON;

-- SET @uri = LOWER(@uri)

-- DECLARE @exist BIT = 0
--         ,@uriUnique BIT = 1

-- SELECT @exist=1 FROM CI_MIDDLEWAY..mw_evento_extrainfo WHERE id_evento=@id_evento


-- SELECT @uriUnique=0 FROM CI_MIDDLEWAY..mw_evento_extrainfo WHERE id_evento!=@id_evento AND LOWER(uri)='/evento/'+lower(@uri)

-- IF @uriUnique = 0
-- BEGIN
--     SELECT 0 success
--             ,'URINOTUNIQUE' msg

--     RETURN;
-- END

-- IF @exist = 0
-- BEGIN
--     INSERT INTO CI_MIDDLEWAY..mw_evento_extrainfo (id_evento, cardimage, cardbigimage, imageoriginal, [uri])
--     SELECT @id_evento,'/evento/{id}/{default_card}', '/evento/{id}/{default_big}', '/ori/{id}/{default_ori}','/evento/'
-- END

DECLARE @id_local_evento INT
SELECT @id_local_evento=id_local_evento FROM CI_MIDDLEWAY..mw_evento where id_evento=@id_evento

IF @address IS NOT NULL AND @address != '' AND @id_local_evento IS NOT NULL
BEGIN
    UPDATE CI_MIDDLEWAY..mw_local_evento
    SET ds_googlemaps=@address
    WHERE id_local_evento=@id_local_evento AND ds_googlemaps!=@address
END

UPDATE CI_MIDDLEWAY..mw_evento_extrainfo
SET [description]=@description
--    ,uri='/evento/'+@uri
    -- ,[address]=@address
WHERE id_evento=@id_evento

SELECT 1 success
        ,'' msg