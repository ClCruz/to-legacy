

ALTER Procedure DBO.SP_CLI_INS002
@Nome    varchar(50), 
@CPF    varchar(14), 
@DDD    char(3),
@Telefone   varchar(20),
@Ramal   char(4),
@RG varchar(15),
@Email varchar(150) = null,
@Assinatura bit = 0

AS
 SET NOCOUNT ON
 DECLARE @Codigo int
 SELECT @Codigo = COALESCE((SELECT MAX(Codigo) FROM tabCliente),0)+1

 INSERT INTO tabCliente  (--Codigo, 
    Nome, CPF, DDD, Telefone, Ramal, StaCliente,RG,Email, Assinatura) 
   VALUES (--@Codigo, 
   @Nome, @CPF, @DDD, @Telefone, @Ramal,'A',@RG,@EMail, @Assinatura)
   SET NOCOUNT OFF   
   SELECT SCOPE_IDENTITY() Codigo