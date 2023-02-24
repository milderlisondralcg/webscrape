
/* Create export from Products Delta table */
select
       t1.catalog_id as 'Catalog Number',
       t2.name AS 'Product Name',
       t1.date_20220307 AS 'Stock 03072022',
       t1.date_20220309 AS 'Stock 03092022',
       t1.date_20220310 AS 'Stock 03102022',
       t1.date_20220311 AS 'Stock 03112022',
       t1.date_20220314 AS 'Stock 03142022',
       t1.date_20220321 AS 'Stock 03212022',
       t1.date_20220322 AS 'Stock 03222022',
       t1.date_20220323 AS 'Stock 03232022',
        t1.date_20220408 AS 'Stock 04082022',
       t1.date_20220411 AS 'Stock 04112022',
       t1.date_20220413 AS 'Stock 04132022',
       t1.date_20220420 AS 'Stock 04202022',
       t1.date_20220427 AS 'Stock 04272022',
       t1.date_20220429 AS 'Stock 04292022',
       t1.date_20220430 AS 'Stock 04302022',
       t1.date_20220505 AS 'Stock 05052022',
       t1.date_20220510 AS 'Stock 05102022',
       t1.date_20220517 AS 'Stock 05172022',
       t1.date_20220524 AS 'Stock 05242022',
       t1.date_20220624 AS 'Stock 06242022',
       t1.date_20220627 AS 'Stock 06272022',
       t1.date_20220628 AS 'Stock 06282022',
       t1.date_20220629 AS 'Stock 06292022',
        t1.date_20220814 AS 'Stock 08142022',
        t1.date_20220817 AS 'Stock 08172022',
        t1.date_20220820 AS 'Stock 08202022',
        t1.date_20220822 AS 'Stock 08222022',
        t1.date_20220824 AS 'Stock 08242022',
        t1.date_20220829 AS 'Stock 08292022',
        t1.date_20220830 AS 'Stock 08302022',
        t1.date_20220831 AS 'Stock 08312022',
       t1.date_20220903 AS 'Stock 09032022',
       t1.date_20220906 AS 'Stock 09062022',
       t1.date_20220907 AS 'Stock 09072022',
       t1.date_20220911 AS 'Stock 09112022',
       t1.date_20220912 AS 'Stock 09122022',
       t1.date_20220913 AS 'Stock 09132022',
       t1.date_20220914 AS 'Stock 09142022',
       t1.date_20220915 AS 'Stock 09152022',
       t1.date_20220919 AS 'Stock 09192022',
       t1.date_20220928 AS 'Stock 09282022',
       t1.date_20220930 AS 'Stock 09302022',
       t1.date_20221013 AS 'Stock 10132022',
       t1.date_20221014 AS 'Stock 10142022',
       t1.date_20221017 AS 'Stock 10172022',
       t1.date_20221019 AS 'Stock 10192022',
       t1.date_20221026 AS 'Stock 10262022',
       t1.date_20221027 AS 'Stock 10272022',
       t1.date_20221030 AS 'Stock 10302022',
       t1.date_20221114 AS 'Stock 11142022',
       t1.date_20221121 AS 'Stock 11212022',
       t1.date_20221128 AS 'Stock 11282022',
       t1.delta as 'Delta'
from productsdelta t1
left join biolegendproducts t2
on t1.catalog_id = t2.catalog_id
order by t2.name;

select * from productsinventory
where catalog_id in ( select catalog_id from
biolegendlinks where status = 'Done' )
and date_acquired in ("2022-12-05");

select t1.catalog_id, t1.last_modified, t1.* from productsinventory t1
where t1.last_modified >= '2022-11-01'
and t1.catalog_id = '100111'
order by t1.last_modified DESC;
/*
update productsinventory
set date_acquired = '2022-03-23'
where last_modified >= '2022-03-23'
and last_modified < '2022-03-24';
*/

/* Check for duplicates within a table */
SELECT catalog_id,  COUNT(*) c FROM productsinventoryjanuary
where date_acquired in ("2023-01-12")
GROUP BY catalog_id HAVING c > 1;

INSERT INTO productsinventoryseptember_copy
SELECT * FROM productsinventoryseptember
where date_acquired IN  ("2022-09-30")
GROUP BY catalog_id;


/* Retrieve COUNT of records within October table based on date acquired */
select count(*) from productsinventory_october
where date_acquired in ("2022-10-13")
and delta_status IS NULL;

/* Retrieve all columns based on date acquired */
select count(*) from productsinventory_march
where date_acquired in ("2022-03-11")
and delta_status IS  NULL;

select * from
productsinventory
where last_modified >= "2022-04-01"
and last_modified < "2022-05-01"

select catalog_id, inventory,date_acquired
from productsinventoryapril
order by catalog_id;

