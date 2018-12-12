ALTER PROCEDURE dbo.seqCodVenda (@id BIGINT)

AS

-- DECLARE @id BIGINT

-- SELECT @id=5000016

SET NOCOUNT ON;

IF OBJECT_ID('tempdb.dbo.#array', 'U') IS NOT NULL
    DROP TABLE #array; 

create table #array (indice int, letra char(1))

insert into #array values (0, 'O')
insert into #array values (1, 'A')
insert into #array values (2, 'B')
insert into #array values (3, 'C')
insert into #array values (4, 'D')
insert into #array values (5, 'E')
insert into #array values (6, 'F')
insert into #array values (7, 'G')
insert into #array values (8, 'H')
insert into #array values (9, 'I')
insert into #array values (10, 'J')
insert into #array values (11, 'K')
insert into #array values (12, 'L')
insert into #array values (13, 'M')
insert into #array values (14, 'N')
insert into #array values (15, 'O')
insert into #array values (16, 'P')
insert into #array values (17, 'Q')
insert into #array values (18, 'R')
insert into #array values (19, 'S')
insert into #array values (20, 'T')
insert into #array values (21, 'U')
insert into #array values (22, 'V')
insert into #array values (23, 'X')
insert into #array values (24, 'W')
insert into #array values (25, 'Y')
insert into #array values (26, 'Z')
insert into #array values (27, '9')
insert into #array values (28, '7')
insert into #array values (29, '5')
insert into #array values (30, '3')
insert into #array values (31, '1')


DECLARE @codVenda VARCHAR(20) = ''
        ,@helper VARCHAR(20) = ''
        ,@len INT = 0
        ,@year INT = substring(convert(varchar(4),year(getdate())),3,2) --1
        ,@month INT = month(getdate()) --2
        ,@day INT = day(getdate()) --3
        ,@hour INT = datepart(hh, getdate()) --4
        ,@minuteFull INT = datepart(mi, getdate()) --5
        ,@minuteWith1_1 INT = left(convert(char(2), datepart(mi, getdate())),1)
        ,@minuteWIth1_2 INT = right(convert(char(2), datepart(mi, getdate())),1)
        ,@secondsFull INT = datepart(ss, getdate()) --6
        ,@secondsWith1_1 INT = left(convert(char(2), datepart(ss, getdate())),1)
        ,@secondsWith1_2 INT = right(convert(char(2), datepart(ss, getdate())),1)
        ,@milesecondsFULL INT = datepart(ms, getdate())
        ,@milesecondsWith1_1 INT = substring(right(REPLICATE('0',3) + convert(varchar(3),datepart(ms, getdate())),3),1,1) --7
        ,@milesecondsWith1_2 INT = substring(right(REPLICATE('0',3) + convert(varchar(3),datepart(ms, getdate())),3),2,1) --8
        ,@milesecondsWith1_3 INT = substring(right(REPLICATE('0',3) + convert(varchar(3),datepart(ms, getdate())),3),3,1) --9
        ,@id_0 INT = right(REPLICATE('0',4) + convert(varchar(100),@id),4)


    DECLARE @id_1 INT = substring(CONVERT(VARCHAR(4),@id_0),1,1)
            ,@id_2 INT = substring(CONVERT(VARCHAR(4),@id_0),2,1)
            ,@id_3 INT = substring(CONVERT(VARCHAR(4),@id_0),3,1)
            ,@id_4 INT = substring(CONVERT(VARCHAR(4),@id_0),4,1)

--1
SET @helper='8'
SELECT @helper = letra FROM #array where indice = @day
SELECT @codVenda=@codVenda+@helper

--2
SET @helper='6'
SELECT @helper = letra FROM #array where indice = @minuteFull
SELECT @codVenda=@codVenda+@helper

--3
SET @helper='4'
SELECT @helper = letra FROM #array where indice = @secondsFull
SELECT @codVenda=@codVenda+@helper

--4
SET @helper='2'
SELECT @helper = letra FROM #array where indice = @milesecondsWith1_1
SELECT @codVenda=@codVenda+@helper

--5
SET @helper='2'
SELECT @helper = letra FROM #array where indice = @milesecondsWith1_2
SELECT @codVenda=@codVenda+@helper

--6
SET @helper='2'
SELECT @helper = letra FROM #array where indice = @milesecondsWith1_3
SELECT @codVenda=@codVenda+@helper

--7
SET @helper='8'
SELECT @helper = letra FROM #array where indice = @id_1
SELECT @codVenda=@codVenda+@helper

--8
SET @helper='8'
SELECT @helper = letra FROM #array where indice = @id_2
SELECT @codVenda=@codVenda+@helper

--9
SET @helper='8'
SELECT @helper = letra FROM #array where indice = @id_3
SELECT @codVenda=@codVenda+@helper

--10
SET @helper='8'
SELECT @helper = letra FROM #array where indice = @id_4
SELECT @codVenda=@codVenda+@helper


SET @len = len(@codVenda)

SELECT @codVenda code