-- =============================================
-- Author:		Emerson Capreti
-- Create date: 08/10/10
-- Alteração em: 15/03/11 --Atualiza o campo in_vende_itau na mw_evento
-- Alteração em: 28/03/11 --Adicionado o campo id_local_evento. Edicarlos Barbosa
-- Alteração em: 03/08/11 --Adicionado o campo in_entrega_ingresso no insert. Edicarlos Barbosa
-- Alteração em: 12/07/16 --Adicionado os campos qt_ingr_por_pedido, in_obriga_CPF_Pos, in_imprimi_canhoto_Pos, in_exibe_tela_assinante. Jefferson Ferreira
-- Description:	Atualiza os eventos no Middleway
-- =============================================
ALTER TRIGGER dbo.tr_atualiza_eventos 
   ON  TABPECA
   AFTER INSERT,DELETE,UPDATE
AS 
BEGIN
	-- SET NOCOUNT ON added to prevent extra result sets from
	-- interfering with SELECT statements.
	SET NOCOUNT ON;

	declare @codpeca smallint,
			@nompeca varchar(35),
			@stapeca char(1),
			@in_vende_site char(1),
			@id_base int,
            @in_bin_itau char(1),
			@id_local_evento int,
			@qt_ingr_por_pedido smallint,
			@in_obriga_CPF_Pos char(1),
			@in_imprimi_canhoto_Pos char(1),
			@in_exibe_tela_assinante char(1),
			@codTipPeca INT,
			@QT_HR_ANTECED INT


	select @id_base = id_base from ci_middleway..mw_base where ds_nome_base_sql = DB_NAME()

	if @id_base is not null
		begin	
			if exists (Select 1 from inserted)
				begin
					select @codpeca = codpeca, @nompeca = nompeca, @stapeca = stapeca, 
					@in_vende_site = in_vende_site, @in_bin_itau = in_bin_itau, @id_local_evento = id_local_evento,
					@qt_ingr_por_pedido = QtIngrPorPedido, 
					@in_obriga_CPF_Pos = ObrigaCPFPos,
					@in_imprimi_canhoto_Pos = ImprimiCanhotoPos,
					@in_exibe_tela_assinante = ExibeTelaAssinante,
					@codTipPeca=CodTipPeca,
					@QT_HR_ANTECED=qt_hr_anteced


					from inserted

					if exists (Select 1 from ci_middleway..mw_evento where codpeca = @codpeca and id_base = @id_base)
						begin
                            DECLARE @changedURL BIT = 0
                                    ,@olderName VARCHAR(1000)
                                    ,@olderLocal INT
									,@id_genre INT

                            SELECT @olderName=e.ds_evento, @olderLocal=e.id_local_evento FROM CI_MIDDLEWAY..mw_evento e WHERE e.CodPeca=@codpeca AND e.id_base=@id_base

							SELECT @id_genre=g.id
							FROM tabPeca p 
							INNER JOIN CI_RAPOSO..tabTipPeca tp ON p.CodTipPeca=tp.CodTipPeca 
							INNER JOIN CI_MIDDLEWAY..genre g ON RTRIM(LTRIM(tp.TipPeca))=RTRIM(LTRIM(g.name)) COLLATE SQL_Latin1_General_Cp1251_CS_AS 
							WHERE p.codPeca=@codPeca

							IF (@id_genre IS NULL)
							BEGIN
								DECLARE @ds_genre VARCHAR(1000)
								SELECT @ds_genre=TipPeca FROM tabTipPeca WHERE CodTipPeca=@codTipPeca
								
								INSERT INTO CI_MIDDLEWAY..genre([name],active)
								SELECT @ds_genre,1
								
								SET @id_genre = SCOPE_IDENTITY()
							END

							UPDATE ci_middleway..mw_evento 
							SET ds_evento = @nompeca,
							in_ativo = case @stapeca when 'I' then 0 else @in_vende_site end,
							in_vende_itau = case when @stapeca = 'A' and @in_bin_itau = 1 then 1 else 0 end,
							id_local_evento = @id_local_evento,
							qt_ingr_por_pedido = @qt_ingr_por_pedido,
							in_obriga_CPF_Pos = @in_obriga_CPF_Pos,
							in_imprimi_canhoto_Pos = @in_imprimi_canhoto_Pos,
							in_exibe_tela_assinante = @in_exibe_tela_assinante
							WHERE CodPeca = @codpeca AND id_base = @id_base

                            IF @olderName<>@nompeca OR (@id_local_evento IS NOT NULL AND @olderLocal IS NULL) OR (@id_local_evento IS NOT NULL AND @olderLocal IS NOT NULL AND @id_local_evento<>@olderLocal)
                                SET @changedURL=1

                            IF @changedURL=1
                            BEGIN
                                UPDATE eei
                                SET eei.uri='/evento/' + replace(replace(lower(CI_MIDDLEWAY.dbo.RemoveSpecialChars(e.ds_evento collate SQL_Latin1_General_Cp1251_CS_AS) COLLATE SQL_Latin1_General_CP1_CI_AS),'-',''),' ', '_')
                                    + '_' + replace(replace(lower(CI_MIDDLEWAY.dbo.RemoveSpecialChars((CASE WHEN le.id_local_evento IS NULL THEN b.ds_nome_teatro ELSE le.ds_local_evento END) collate SQL_Latin1_General_Cp1251_CS_AS) COLLATE SQL_Latin1_General_CP1_CI_AS),'-',''),' ', '_')
                                    + '_' + CONVERT(VARCHAR(10),e.id_evento)                            
                                FROM CI_MIDDLEWAY..mw_evento_extrainfo eei
                                INNER JOIN CI_MIDDLEWAY..mw_evento e ON eei.id_evento=e.id_evento
                                INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
                                LEFT JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
                                WHERE e.CodPeca=@codpeca AND e.id_base=@id_base
                            END

							UPDATE eei
							SET eei.id_genre = @id_genre
								,eei.minuteBefore=(@QT_HR_ANTECED*60)
							FROM CI_MIDDLEWAY..mw_evento_extrainfo eei
							INNER JOIN CI_MIDDLEWAY..mw_evento e ON eei.id_evento=e.id_evento
							INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
							LEFT JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
							WHERE e.CodPeca=@codpeca AND e.id_base=@id_base
							
                            UPDATE s
                            SET s.outofdate=1
                            FROM CI_MIDDLEWAY..search s
                            INNER JOIN CI_MIDDLEWAY..mw_evento e ON s.id_evento=e.id_evento
                            WHERE e.CodPeca=@codpeca AND e.id_base=@id_base

                            UPDATE s
                            SET s.outofdate=1
                            FROM CI_MIDDLEWAY..home s
                            INNER JOIN CI_MIDDLEWAY..mw_evento e ON s.id_evento=e.id_evento
                            WHERE e.CodPeca=@codpeca AND e.id_base=@id_base
						end
					else
						begin
							insert into ci_middleway..mw_evento (ds_evento, codpeca, id_base,
							in_ativo, in_vende_itau, id_local_evento, in_entrega_ingresso, qt_ingr_por_pedido, in_obriga_CPF_Pos, in_imprimi_canhoto_Pos, in_exibe_tela_assinante)
							values (@nompeca, @codpeca, @id_base, case @stapeca when 'I' then 0					
									else @in_vende_site end, case when @stapeca = 'A' and @in_bin_itau = 1 then 1 else 0 end,
									@id_local_evento, 0, @qt_ingr_por_pedido, @in_obriga_CPF_Pos, @in_imprimi_canhoto_Pos, @in_exibe_tela_assinante)
                            

                            INSERT INTO CI_MIDDLEWAY..mw_evento_extrainfo (id_evento, cardimage, cardbigimage, imageoriginal, [uri], minuteBefore)
                                SELECT DISTINCT e.id_evento,'/evento/{id}/{default_card}', '/evento/{id}/{default_big}', '/ori/{id}/{default_ori}','/evento/' + replace(replace(lower(CI_MIDDLEWAY.dbo.RemoveSpecialChars(e.ds_evento collate SQL_Latin1_General_Cp1251_CS_AS) COLLATE SQL_Latin1_General_CP1_CI_AS),'-',''),' ', '_')
                                + '_' + replace(replace(lower(CI_MIDDLEWAY.dbo.RemoveSpecialChars((CASE WHEN le.id_local_evento IS NULL THEN b.ds_nome_teatro ELSE le.ds_local_evento END) collate SQL_Latin1_General_Cp1251_CS_AS) COLLATE SQL_Latin1_General_CP1_CI_AS),'-',''),' ', '_')
                                + '_' + CONVERT(VARCHAR(10),e.id_evento)
								,(@QT_HR_ANTECED*60)
                                from CI_MIDDLEWAY..mw_evento e
                                INNER JOIN CI_MIDDLEWAY..mw_base b ON e.id_base=b.id_base
                                LEFT JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
                                WHERE e.CodPeca=@codpeca AND e.id_base=@id_base


						end
				end
			else
				begin
					UPDATE ci_middleway..mw_evento 
					SET ds_evento = @nompeca,
						in_ativo = 0, 
						in_vende_itau = 0,
						id_local_evento = @id_local_evento
					WHERE CodPeca = (select codpeca from deleted) AND id_base = @id_base
				end
		end
END