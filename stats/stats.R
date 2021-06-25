#!/usr/bin/Rscript --vanilla

library("RPostgreSQL")
library(tidyverse)

set_utf8 <- function(x) {
  # Declare UTF-8 encoding on all character columns:
  chr <- sapply(x, is.character)
  x[, chr] <- lapply(x[, chr, drop = FALSE], `Encoding<-`, "UTF-8")
  # Same on column names:
  Encoding(names(x)) <- "UTF-8"
  x
}

# join answers dataframe to responses/communities/weeks
data_prepare <- function(qids) {
  answers %>% 
    filter(question_id %in% qids) %>%
    inner_join(responses, by='response_id') %>% 
    inner_join(communities, by='community_id') %>%
    inner_join(weeks, by='week_id')
}

# filter data, so that it is between first and last dates
date_filter <- function(df, first, last) {
  # first date not given, so use end of January
  if (missing(first)) {
    first <- "2021-01-31"
  }
  # last date not given, so use date given on command line
  if (missing(last)) {
    last <- date
  }
  
  df %>% filter(week > first) %>% filter(week < last)
}

# tidy data ready for CSV export - choose and rename columns and handle missing data
data_tidy <- function(df, columns) {
  df <- df %>% select(week, community_string, numeric_value)
  colnames(df) <- columns
  df %>% complete(week, community) %>% arrange(week, community)
}

#Fig. 2 Number of health personnel per community
fig2 <- function() {
  data <- data_prepare(c(72))
  data <- date_filter(data)
  data_tidy(data, c("week", "community", "total_number_health_personnel"))
}

#Fig. 3 Number of infections in fishing community
fig3 <- function() {
  data <- data_prepare(c(152))
  data <- date_filter(data)
  data <- data_tidy(data, c("week", "community", "total_number_infections"))

  data_b <- data_prepare(c('147'))
  data_b <- date_filter(data_b)
  data_b <- data_tidy(data_b, c( "week","community", "total_number_deaths"))

  #cbind(data, data_b)
  merge(data, data_b, by=c('week', 'community'))
}

#Fig. 7 Quantity of available oxygen
fig7 <- function() {
  data <- data_prepare(c(121))
  data <- date_filter(data)
  data_tidy(data, c("week", "community", "oxygen_availability"))
}

#Fig. 8 Numero de pruebas
#might be good to add per 100 000 habitants?
fig8 <- function() {
  data <- data_prepare(c(118,112,109,115))
  data <- date_filter(data)

  #only select columns to plot
  data <- data %>% select(week, community_string, numeric_value)
  colnames(data)<-c("week", "community", "number_tests")

  #add values for each test
  data <- data %>% group_by(week, community) %>% summarise(n_tests=sum(number_tests, na.rm=TRUE))

  data %>% complete(week, community)#complete missing levels of date per community
}

#Fig. 11 Number of vaccinated people
#might be good to add per 100 000 habitants?
fig11 <- function() {
  data <- data_prepare(c(162,163,164,165))
  data <- date_filter(data)

  #only select columns to plot
  data <- data %>% select(week, community_string, numeric_value)
  colnames(data)<-c("week", "community", "number_vaccines")

  #add values for each test
  data <- data %>% group_by(week, community) %>% summarise(number_vaccines=sum(number_vaccines, na.rm=TRUE))

  data %>% complete(week, community)#complete missing levels of date per community
}

#Fig. 12 Landings and prices
fig12 <- function() {
  data <- data_prepare(c(78,79,80,81))
  data <- date_filter(data)

  data$unique <- paste(data$community_string, data$week, data$`repeat`)
  
  data <- data %>% select(question_id, string_value, community_string, week, numeric_value, unique)

  data_a <- data %>% filter(question_id=="78")
  data_b <- data %>% filter(question_id=="79") %>% select(unique,numeric_value)
  data_c <- data %>% filter(question_id=="80") %>% select(unique,numeric_value)
  data_d <- data %>% filter(question_id=="81") %>% select(unique,numeric_value)

  data_e <- data_a %>% full_join(data_b, by = "unique")
  data_e <- data_e %>% full_join(data_c, by = "unique")
  data_e <- data_e %>% full_join(data_d, by = "unique")
  
  data_e <- data_e %>% select(question_id, string_value, community_string, week, numeric_value.y, numeric_value.x.x, numeric_value.y.y)
  colnames(data_e) <- c("question_id", "species", "community", "week", "landings","min_price","max_price")
  
  data_e$mean_price <- (data_e$min_price + data_e$max_price) / 2
  data_e$land_value <- data_e$landings * data_e$mean_price

  data_f <- data_e %>% complete(community, species, week) %>% filter(!is.na(week)) %>% inner_join(community_species)
  
  # reorder and remove columns
  data_f[, c('species', 'community', 'week', 'landings', 'land_value', 'min_price', 'max_price', 'mean_price')]
}

#Fig. 14 Number of active sellers
fig14 <- function() {
  data <- data_prepare(c('103'))
  data <- date_filter(data)
  data_tidy(data, c("week", "community", "number_buyers"))
}

#Fig. 16 Number of active fishing vessels
fig16 <- function() {
  data <- data_prepare(c('1'))
  data <- date_filter(data)
  data_tidy(data, c("week", "community", "number_active_vessels"))
}

# get date as first argument from command line
argv <- commandArgs(trailingOnly=TRUE)
if (length(argv) != 1) {
  stop("Usage: ./stats.R YYYY-MM-DD", call.=FALSE)
}

date <- argv[1]

# read credentials from pgpass file
deets <- scan(file="~/.pgpass", sep=":", what=list('', '', '', '', ''))
line <- 4 # line to choose

# connect to database
drv <- dbDriver("PostgreSQL")
con <- dbConnect(drv, 
  dbname=deets[[3]][line], 
  host=deets[[1]][line],
  port=deets[[2]][line],
  user=deets[[4]][line],
  password=deets[[5]][line],
  forceISOdate=TRUE)

# fetch data from database
answers <- set_utf8(dbGetQuery(con, "SELECT * FROM answers;"))
communities <- set_utf8(dbGetQuery(con, "SELECT * FROM communities;"))
responses <- set_utf8(dbGetQuery(con, "SELECT * FROM responses;"))
weeks <- set_utf8(dbGetQuery(con, "SELECT * FROM weeks;"))
community_species <- set_utf8(dbGetQuery(con, "SELECT species_string AS species, community_string AS community FROM species, communities WHERE EXISTS (SELECT 1 FROM answers INNER JOIN responses USING (response_id) WHERE responses.community_id = communities.community_id AND question_id = 78 AND string_value = species_string);"))

# call functions for each figure's CSV
funcs <- c('fig2', 'fig3', 'fig7', 'fig8', 'fig11', 'fig12', 'fig14', 'fig16')

figs <- NA
landings <- NA

for (f in funcs) {
  if (f == 'fig12') {
    landings <- get(f)()
  } else {
    if (!is.data.frame(figs)) {
      figs <- get(f)()
    } else {
      figs <- merge(figs, get(f)(), by=c('week', 'community'))
    }
  }
}

write.csv(landings, paste0(date, '/landings.csv'), row.names = FALSE)
write.csv(figs, paste0(date, '/figs.csv'), row.names = FALSE)
