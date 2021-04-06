/****f* functions.sql/getCommunity
 * NAME
 * getCommunity
 * SYNOPSIS
 * Return ID of community identified by string
 * ARGUMENTS
 *   * community - string - used to identify community
 * RETURN VALUE
 * INTEGER - community ID 
 ******
 */
CREATE OR REPLACE FUNCTION  getCommunity ( --{{{
  in_community VARCHAR(32)
)
RETURNS TABLE (
  community_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT c.community_id 
      FROM communities AS c
     WHERE c.community_string = in_community;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
--}}}

/****f* functions.sql/getCommunities
 * NAME
 * getCommunities
 * SYNOPSIS
 * Return ID and name of each community
 * RETURN VALUE
 * INTEGER, VARCHAR(32) - community ID and name
 ******
 */
CREATE OR REPLACE FUNCTION getCommunities () --{{{
RETURNS TABLE (
  community_id INTEGER,
  community_string VARCHAR(32)
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT c.community_id, c.community_string
      FROM communities AS c
  ORDER BY c.community_id;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
--}}}

/****f* functions.sql/getWeek
 * NAME
 * getWeek
 * SYNOPSIS
 * Return the ID of the week starting on the given date.
 * Insert a new date if it isn't found
 * ARGUMENTS
 *   * week - DATE - the date the week starts
 * RETURN VALUE
 * INTEGER - week ID
 ******
 */
CREATE OR REPLACE FUNCTION getWeek ( --{{{
  in_date DATE
)
RETURNS TABLE (
  week_id INTEGER
)
AS $FUNC$
  DECLARE temp_week_id INTEGER;
BEGIN
  -- try selecting from weeks
  SELECT w.week_id
    FROM weeks AS w
   WHERE w.week = in_date
    INTO temp_week_id;
  
  -- not found, so insert and get new ID back
  IF NOT FOUND THEN
    INSERT 
      INTO weeks AS w
           (week)
    VALUES (in_date)
 RETURNING w.week_id 
      INTO temp_week_id;
  END IF;
  
  RETURN QUERY
    SELECT temp_week_id;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER VOLATILE;
--}}}

/****f* functions.sql/addSeries
 * NAME
 * addSeries
 * SYNOPSIS
 * Add a series (list of values, e.g. age ranges)
 * ARGUMENTS
 *   * series_string - STRING - name of series
 * RETURN VALUE
 * INTEGER - series ID
 ******
 */
CREATE OR REPLACE FUNCTION addSeries ( --{{{
  in_series_string VARCHAR(64)
)
RETURNS TABLE (
  series_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    INSERT
      INTO series AS s
           (series_string)
    VALUES (in_series_string)
 RETURNING s.series_id;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER VOLATILE;
--}}}

/****f* functions.sql/getSeries
 * NAME
 * getSeries
 * SYNOPSIS
 * Get information about series using series string identifier
 * ARGUMENTS
 *   * series_string - string - string identifier for series
 * RETURN VALUE
 * Table of series_id, item_id, item_string, item_number
 ******
 */
CREATE OR REPLACE FUNCTION getSeries ( --{{{
  in_series_string VARCHAR(64)
)
RETURNS TABLE (
  series_id INTEGER,
  item_id INTEGER,
  item_string VARCHAR(64),
  item_number INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT s.series_id, 
           i.item_id, i.item_string, i.item_number
      FROM series AS s
INNER JOIN series_items AS i USING (series_id)
     WHERE series_string = in_series_string
  ORDER BY i.item_number ASC;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
--}}}

/****f* functions.sql/addSeriesItem
 * NAME
 * addSeriesItem
 * SYNOPSIS
 * Add an item to a series
 * ARGUMENTS
 *   * series_id - INTEGER - ID of series
 *   * item_string - STRING - name of item
 *   * item_number - INTEGER - number in series
 * RETURN VALUE
 * INTEGER - item ID
 ******
 */
CREATE OR REPLACE FUNCTION addSeriesItem ( --{{{
  in_series_id INTEGER,
  in_item_string VARCHAR(128),
  in_item_number INTEGER
)
RETURNS TABLE (
  item_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    INSERT
      INTO series_items AS i
           (series_id, item_string, item_number)
    VALUES (in_series_id, in_item_string, in_item_number)
 RETURNING i.item_id;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER VOLATILE;
--}}}

/****f* functions.sql/getSeriesItem
 * NAME
 * getSeriesItem
 * SYNOPSIS
 * Get ID of series item using question string and item number
 * ARGUMENTS
 *   * question_string - string - name of question
 *   * item_number - INTEGER - number of item
 * RETURN VALUE
 * item_id INTEGER
 ******
 */
CREATE OR REPLACE FUNCTION getSeriesItem ( --{{{
  in_series_string VARCHAR(128),
  in_item_number INTEGER
)
RETURNS TABLE (
  item_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT i.item_id
      FROM series_items AS i
INNER JOIN series AS s USING (series_id)
     WHERE s.series_string = in_series_string
       AND i.item_number = in_item_number;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
--}}}

/****f* functions.sql/addMetaQuestion
 * NAME
 * addMetaQuestion
 * SYNOPSIS
 * Add a meta question record for grouping several questions
 * RETURN VALUE
 * INTEGER - meta question ID
 ******
 */
CREATE OR REPLACE FUNCTION addMetaQuestion () --{{{
RETURNS TABLE (
  meta_question_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    INSERT 
      INTO meta_questions AS m
           (meta_question_id)
    VALUES (DEFAULT)
 RETURNING m.meta_question_id;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER VOLATILE;
--}}}


/****f* functions.sql/addQuestion
 * NAME
 * addQuestion
 * SYNOPSIS
 * Add a question record- a string and possible item ID
 * ARGUMENTS
 *   * question_string - string - name of question
 *   * item_id - INTEGER - possible ID of item
 *   * repeats - INTEGER - number of times question is repeated
 * RETURN VALUE
 * INTEGER - question ID
 ******
 */
CREATE OR REPLACE FUNCTION addQuestion ( --{{{
  in_meta_question_id INTEGER,
  in_order INTEGER,
  in_question_string VARCHAR(64),
  in_item_id INTEGER,
  in_repeats INTEGER
)
RETURNS TABLE (
  question_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    INSERT 
      INTO questions AS q
           (meta_question_id, order_num, item_id, question_string, repeats)
    VALUES (in_meta_question_id, in_order, in_item_id, in_question_string, in_repeats)
 RETURNING q.question_id;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER VOLATILE;
--}}}

/****f* functions.sql/getQuestion
 * NAME
 * getQuestion
 * SYNOPSIS
 * Get question ID using question string and optional item ID
 * ARGUMENTS
 *   * question_string - string - name of question
 * RETURN VALUE
 * Table of question_id, item_id INTEGERS, repeats
 ******
 */
CREATE OR REPLACE FUNCTION getQuestion ( --{{{
  in_question_string VARCHAR(64)
)
RETURNS TABLE (
  question_id INTEGER,
  question_string VARCHAR(64),
  item_id INTEGER,
  repeats INTEGER,
  item_string VARCHAR(128)
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT q.question_id, q.question_string, q.item_id, q.repeats,
           si.item_string
      FROM questions AS q
INNER JOIN series_items AS si USING (item_id)
     WHERE q.question_string = in_question_string
        OR in_question_string SIMILAR TO CONCAT(q.question_string, '[0-9]%');
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
--}}}

/****f* functions.sql/addResponse
 * NAME
 * addResponse
 * SYNOPSIS
 * Record a response to the survey
 * ARGUMENTS
 *   * community_id - INTEGER - identifier of community
 *   * week_id - INTEGER - ID of date
 * RETURN VALUE
 * INTEGER - response_id
 ******
 */
CREATE OR REPLACE FUNCTION addResponse ( --{{{
  in_community_id INTEGER,
  in_week_id INTEGER
)
RETURNS TABLE (
  response_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    INSERT
      INTO responses AS r
           (community_id, week_id)
    VALUES (in_community_id, in_week_id)
 RETURNING r.response_id;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER VOLATILE;
--}}}

/****f* functions.sql/getResponse
 * NAME
 * getResponse
 * SYNOPSIS
 * Get ID of response given community and week
 * ARGUMENTS
 *   * community_id - INTEGER - identifier of community
 *   * week_id - INTEGER - ID of date
 * RETURN VALUE
 * INTEGER - response_id
 ******
 */
CREATE OR REPLACE FUNCTION getResponse ( --{{{
  in_community_id INTEGER,
  in_week_id INTEGER
)
RETURNS TABLE (
  response_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT r.response_id
      FROM responses AS r
     WHERE community_id = in_community_id
       AND week_id = in_week_id;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
--}}}

/****f* functions.sql/getResponses
 * NAME
 * getResponses
 * SYNOPSIS
 * Get ID of responses given community ID
 * ARGUMENTS
 *   * community_id - INTEGER - identifier of community
 * RETURN VALUE
 * INTEGER - response_id
 ******
 */
CREATE OR REPLACE FUNCTION getResponses ( --{{{
  in_community_id INTEGER
)
RETURNS TABLE (
  response_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT r.response_id
      FROM responses AS r
INNER JOIN weeks AS w USING (week_id)
     WHERE community_id = in_community_id
  ORDER BY w.week;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
--}}}

/****f* functions.sql/addAnswer
 * NAME
 * addAnswer
 * SYNOPSIS
 * Record answer in a response
 * ARGUMENTS
 *   * in_response_id - INTEGER - ID of response
 *   * in_question_id - INTEGER - ID of question
 *   * in_repeat - INTEGER - which repeat of the question is this
 *   * in_answer_numeric - NUMERIC - numeric answer to question
 *   * in_answer_string - string - string answer to question
 * RETURN VALUE
 * BOOLEAN - true when row inserted
 ******
 */
CREATE OR REPLACE FUNCTION addAnswer ( --{{{
  in_response_id INTEGER,
  in_question_id INTEGER,
  in_repeat INTEGER,
  in_answer_numeric NUMERIC,
  in_answer_string TEXT
)
RETURNS TABLE (
  updated BOOLEAN
)
AS $FUNC$
BEGIN
  INSERT
    INTO answers
         (response_id, question_id, repeat, numeric_value, string_value)
  VALUES (in_response_id, in_question_id, in_repeat, 
          in_answer_numeric, in_answer_string);
  
  RETURN QUERY
    SELECT FOUND;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER VOLATILE;
--}}}

/****f* functions.sql/correctAnswer
 * NAME
 * correctAnswer
 * SYNOPSIS
 * Update an answer
 * ARGUMENTS
 *   * in_response_id - INTEGER - ID of response
 *   * in_question_id - INTEGER - ID of question
 *   * in_repeat - INTEGER - which repeat of the question is this
 *   * in_answer_numeric - NUMERIC - numeric answer to question
 *   * in_answer_string - string - string answer to question
 * RETURN VALUE
 * BOOLEAN - true when row updated
 ******
 */
CREATE OR REPLACE FUNCTION correctAnswer ( --{{{
  in_response_id INTEGER,
  in_question_id INTEGER,
  in_repeat INTEGER,
  in_answer_numeric NUMERIC,
  in_answer_string TEXT
)
RETURNS TABLE (
  updated BOOLEAN
)
AS $FUNC$
BEGIN
     INSERT
       INTO answers
            (response_id, question_id, repeat, numeric_value, string_value)
     VALUES (in_response_id, in_question_id, in_repeat, 
             in_answer_numeric, in_answer_string)
ON CONFLICT (response_id, question_id, repeat)
  DO UPDATE 
        SET numeric_value = in_answer_numeric,
            string_value = in_answer_string;
         
  RETURN QUERY
    SELECT FOUND;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER VOLATILE;
--}}}

/****f* functions.sql/export
 * NAME
 * export
 * SYNOPSIS
 * Export a response
 * ARGUMENTS
 *   * in_response_id - INTEGER - ID of response
 * RETURN VALUE
 * Table with question details and answers from response
 ******
 */
CREATE OR REPLACE FUNCTION export ( --{{{
  in_response_id INTEGER
)
RETURNS TABLE (
  question_id INTEGER,
  question_string VARCHAR(64),
  series_string VARCHAR(64),
  item_string VARCHAR(128),
  repeat INTEGER,
  numeric_value NUMERIC,
  string_value TEXT
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT q.question_id, q.question_string,
           s.series_string, si.item_string,
           q.repeat_number,
           a.numeric_value, a.string_value
      FROM (SELECT qi.question_id, qi.order_num, qi.question_string, 
                   qi.item_id, qi.meta_question_id, repeat_number
              FROM questions AS qi, GENERATE_SERIES(1, repeats) AS repeat_number) AS q
 LEFT JOIN series_items AS si USING (item_id)
 LEFT JOIN series AS s USING (series_id)
 LEFT JOIN answers AS a 
        ON a.question_id = q.question_id 
       AND a.repeat = repeat_number 
       AND a.response_id = in_response_id
  ORDER BY q.order_num, q.repeat_number, si.item_number
;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
--}}}

/****f* functions.sql/report
 * NAME
 * report
 * SYNOPSIS
 * Get response as a report
 * ARGUMENTS
 *   * in_response_id - INTEGER - ID of response
 * RETURN VALUE
 * Table with question details and answers from response
 ******
 */
CREATE OR REPLACE FUNCTION report ( --{{{
  in_response_id INTEGER
)
RETURNS TABLE (
  question_id INTEGER,
  question_string VARCHAR(255),
  string_value TEXT
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT r.question_id, r.question_string::VARCHAR(255), r.string_value
      FROM (SELECT CASE WHEN q.question_id = 166 THEN 0 ELSE 1 END AS ord1,
                   q.question_id, 
                   q.question_string || COALESCE(' ' || si.item_string, '') || ' (' || q.repeat_number || ')' AS question_string,
                   a.string_value,
                   q.order_num, q.repeat_number, si.item_number
              FROM (SELECT qi.question_id, qi.order_num, qi.question_string, 
                           qi.item_id, qi.meta_question_id, repeat_number
                      FROM questions AS qi, GENERATE_SERIES(1, repeats) AS repeat_number) AS q
         LEFT JOIN series_items AS si USING (item_id)
         LEFT JOIN series AS s USING (series_id)
         LEFT JOIN answers AS a 
                ON a.question_id = q.question_id 
               AND a.repeat = repeat_number 
               AND a.response_id = in_response_id) AS r
  ORDER BY r.ord1, r.order_num, r.repeat_number, r.item_number
;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
--}}}

/****f* functions.sql/reorder
 * NAME
 * reorder
 * SYNOPSIS
 * Reorder answers to given question, using item_string to identify sub-answer to sort on
 * ARGUMENTS
 *   * in_response_id - INTEGER - ID of response to reorder
 *   * in_question_string - VARCHAR - question to reorder answers to
 *   * in_item_string - VARCHAR - sub-answer to use for ordering
 * RETURN VALUE
 * Boolean - true when answers updated
 ******
 */
CREATE OR REPLACE FUNCTION reorder ( --{{{
  in_response_id INTEGER,
  in_question_string VARCHAR(64),
  in_item_string VARCHAR(128)
)
RETURNS TABLE (
  updated BOOLEAN
)
AS $FUNC$
BEGIN
    -- update repeat in answers to avoid collisions
    UPDATE answers AS a
       SET repeat = repeat + q.repeats
      FROM questions AS q
     WHERE q.question_id = a.question_id
       AND q.question_string = in_question_string
       AND a.response_id = in_response_id;
       
    -- update repeat again using ordering based on in_item_string
    UPDATE answers AS a
       SET repeat = o.new_repeat
      FROM questions AS q,
           (SELECT repeat AS old_repeat, ROW_NUMBER() OVER (ORDER BY string_value) AS new_repeat
              FROM answers AS ao
        INNER JOIN questions as qo USING (question_id)
        INNER JOIN series_items AS sio USING (item_id)
             WHERE qo.question_string = in_question_string
               AND sio.item_string = in_item_string
               AND ao.response_id = in_response_id) AS o
     WHERE q.question_id = a.question_id
       AND q.question_string = in_question_string
       AND a.response_id = in_response_id
       AND a.repeat = o.old_repeat;
    
    RETURN QUERY
      SELECT FOUND;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER VOLATILE;
--}}}

