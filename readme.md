# IMDB data files parser

## Usage:

Analyze a data file:

```shell
php parser.php analyze data/title.basics.tsv --format=both --rows=1000 --table=title_basics --show_max_values
```

Load a data file and create a SQL file:

```shell
php parser.php load data/title.basics.tsv ./target_dir --table=title_basics -rows 1000 --limit=5000
```

## Typical usage example:

Download the IMDB data file (`.gz`)

Unzip it into the `/data` folder of this utility (and rename it so that you can recognize it, e.g. to `title.basics.tsv`)

In the following command replace:

* `title.basics.tsv` with the name of your unzipped file
* `title_basics` with the name of your intended database table (optional)

Then run it.

```shell
php parser.php analyze data/title.basics.tsv --format=both --table=title_basics --show_max_values
```

This will analyze the columns and suggest a database structure. Use this suggestion to create a database table that will be able to hold all the data in the file (I know this could have been done automatically, maybe in the next version).

---

In the following command replace:

* `title.basics.tsv` with the name of your unzipped file
* `./target_dir` with the path to the file where you want the resulting SQL files
* `title_basics` with the name of your intended database table (optional)

```shell
php parser.php load data/title.basics.tsv ./target_dir --table=title_basics
```

This will create a SQL file with inserts. The file expects the same structure that was suggested in the analysis.

---

Then just import the file into your database:

```shell
mysql -u username -p your_database_name < title.basics.sql
```