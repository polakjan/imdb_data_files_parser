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