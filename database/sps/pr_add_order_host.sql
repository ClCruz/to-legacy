CREATE PROCEDURE dbo.pr_add_order_host (@id_pedido_venda INT, @indice INT, @id_cliente INT, @host VARCHAR(1000))

AS

SET NOCOUNT ON;

DECLARE @id UNIQUEIDENTIFIER = NULL

SELECT @id = id FROM CI_MIDDLEWAY..host WHERE host = @host

IF @id IS NULL
BEGIN
    INSERT INTO CI_MIDDLEWAY..host ([NAME], [HOST]) VALUES (@host, @host);

    SELECT @id = id FROM CI_MIDDLEWAY..host WHERE host = @host
END

INSERT INTO order_host (id_pedido_venda, indice, id_host, id_cliente) VALUES (@id_pedido_venda, @indice, @id, @id_cliente)