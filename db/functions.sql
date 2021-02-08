/****f* functions.sql/
 * NAME
 * 
 * SYNOPSIS
 * 
 * ARGUMENTS
 * RETURN VALUE
 ******
 */
/*CREATE OR REPLACE FUNCTION  ( --{{{
)
RETURNS TABLE (
)
AS $FUNC$
BEGIN
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
--}}}
*/

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
  in_series_string VARCHAR(32)
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
  in_series_string VARCHAR(32)
)
RETURNS TABLE (
  series_id INTEGER,
  item_id INTEGER,
  item_string VARCHAR(32),
  item_number INTEGER,
  data_type VARCHAR(8),
  is_multiple BOOLEAN
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT s.series_id, 
           i.item_id, i.item_string, i.item_number, i.data_type, i.is_multiple
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
 *   * date_type - STRING - type of data expected - numeric/string
 *   * is_multiple - BOOLEAN - can question have multiple answers
 * RETURN VALUE
 * INTEGER - item ID
 ******
 */
CREATE OR REPLACE FUNCTION addSeriesItem ( --{{{
  in_series_id INTEGER,
  in_item_string VARCHAR(32),
  in_item_number INTEGER,
  in_data_type VARCHAR(8),
  in_is_multiple BOOLEAN
)
RETURNS TABLE (
  item_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    INSERT
      INTO series_items AS i
           (series_id, item_string, item_number, data_type, is_multiple)
    VALUES (in_series_id, in_item_string, in_item_number, in_data_type, 
            in_is_multiple)
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
 * INTEGER - item_id
 ******
 */
CREATE OR REPLACE FUNCTION getSeriesItem ( --{{{
  in_series_string VARCHAR(32),
  in_item_number INTEGER
)
RETURNS TABLE (
  item_id INTEGER,
  data_type VARCHAR(8),
  is_multiple BOOLEAN
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT i.item_id, i.data_type, i.is_multiple
      FROM series_items AS i
INNER JOIN series AS s USING (series_id)
     WHERE s.series_string = in_series_string
       AND i.item_number = in_item_number;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
--}}}

/****f* functions.sql/addQuestion
 * NAME
 * addQuestion
 * SYNOPSIS
 * Add a question record- a string and possible item ID
 * ARGUMENTS
 *   * question_string - string - name of question
 *   * item_id - INTEGER - possible ID of item
 *   * data - string - type of data (numeric/string)
 * RETURN VALUE
 * INTEGER - question ID
 ******
 */
CREATE OR REPLACE FUNCTION addQuestion ( --{{{
  in_question_string VARCHAR(32),
  in_item_id INTEGER,
  in_type VARCHAR(8),
  in_is_multiple BOOLEAN
)
RETURNS TABLE (
  question_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    INSERT 
      INTO questions AS q
           (item_id, question_string, data_type, is_multiple)
    VALUES (in_item_id, in_question_string, in_type, in_is_multiple)
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
 * Table of question_id, item_id INTEGERS and data type (string)
 ******
 */
CREATE OR REPLACE FUNCTION getQuestion ( --{{{
  in_question_string VARCHAR(32)
)
RETURNS TABLE (
  question_id INTEGER,
  item_id INTEGER,
  data_type VARCHAR(8),
  is_multiple BOOLEAN
)
AS $FUNC$
BEGIN
  RETURN QUERY
    SELECT q.question_id, q.item_id, q.data_type, q.is_multiple
      FROM questions AS q
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

/****f* functions.sql/addStructure
 * NAME
 * addStructure
 * SYNOPSIS
 * Create a new structure ID
 * RETURN VALUE
 * INTEGER - structure_id
 ******
 */
CREATE OR REPLACE FUNCTION addStructure () --{{{
RETURNS TABLE (
  structure_id INTEGER
)
AS $FUNC$
BEGIN
  RETURN QUERY
    INSERT
      INTO structures AS s
           (structure_id)
    VALUES (DEFAULT)
 RETURNING s.structure_id;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER VOLATILE;
--}}}

/****f* functions.sql/addAnswer
 * NAME
 * addAnswer
 * SYNOPSIS
 * Record answer in a response
 * ARGUMENTS
 *   * in_response_id - INTEGER - ID of response
 *   * in_question_id - INTEGER - ID of question
 *   * in_answer_numeric - NUMERIC - numeric answer to question
 *   * in_answer_string - string - string answer to question
 * RETURN VALUE
 * BOOLEAN - true when row inserted
 ******
 */
CREATE OR REPLACE FUNCTION addAnswer ( --{{{
  in_response_id INTEGER,
  in_question_id INTEGER,
  in_structure_id INTEGER,
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
         (response_id, question_id, structure_id, numeric_value, string_value)
  VALUES (in_response_id, in_question_id, in_structure_id, in_answer_numeric, 
          in_answer_string);
  
  RETURN QUERY
    SELECT FOUND;
END;
$FUNC$ LANGUAGE plpgsql SECURITY DEFINER VOLATILE;
--}}}

