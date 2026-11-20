# Weather Service API Documentation



## 1. Overview



The Weather Service API is a versioned REST API developed using CodeIgniter 4. It retrieves current weather information for Iligan City, stores weather records in an SQLite database, and provides endpoints for retrieving saved weather logs in JSON or XML format.



**Base URL:**

```text

http://localhost:8080/api/v1

```



## 2. Authentication



All API endpoints require an API key.



**Header:**

```http

X-API-Key: YOUR_API_KEY

```



Replace `YOUR_API_KEY` with the API key configured in your local `.env` file.



Requests without a valid API key return HTTP 401 Unauthorized.



## 3. API Endpoints



### 3.1 Refresh Weather Data



Retrieves the latest weather information for Iligan City and saves it to the database.



| Property | Value |

|---|---|

| Method | POST |

| Endpoint | `/weather/refresh` |

| Authentication | Required |

| Success Status | 201 Created |



**Example request:**

```http

POST /api/v1/weather/refresh

X-API-Key: YOUR_API_KEY

```



**Example success response:**

```json

{

  "status": 201,

  "data": {

    "id": 1,

    "city": "Iligan City",

    "temperatureC": 25.2,

    "fetchedAt": "2026-11-20 10:30:00"

  }

}

```



The temperature and timestamp will vary depending on the retrieved weather data.



**Possible errors:**

- `401 Unauthorized` – Missing or invalid API key.

- `502 Bad Gateway` – Unable to retrieve valid weather data from the external weather service.



### 3.2 Retrieve Weather Logs



Retrieves saved weather records. The endpoint supports JSON and XML output.



| Property | Value |

|---|---|

| Method | GET |

| Endpoint | `/weather/logs` |

| Authentication | Required |

| Success Status | 200 OK |



**Query Parameters:**



| Parameter | Description | Default | Allowed Values |

|---|---|---|---|

| `limit` | Maximum number of records to retrieve | 10 | 1–50 |

| `format` | Response output format | json | json, xml |



**Example JSON request:**

```http

GET /api/v1/weather/logs?limit=10&format=json

X-API-Key: YOUR_API_KEY

```



**Example JSON response:**

```json

{

  "status": 200,

  "data": [

    {

      "id": 1,

      "city": "Iligan City",

      "temperatureC": 25.2,

      "fetchedAt": "2026-11-20 10:30:00"

    }

  ]

}

```



**Example XML request:**

```http

GET /api/v1/weather/logs?limit=10&format=xml

X-API-Key: YOUR_API_KEY

```



**Example XML response:**

```xml

<?xml version="1.0" encoding="UTF-8"?>

<weatherReport>

    <reading>

        <id>1</id>

        <city>Iligan City</city>

        <temperatureC>25.2</temperatureC>

        <fetchedAt>2026-11-20 10:30:00</fetchedAt>

    </reading>

</weatherReport>

```



XML output is validated using the `app/Schemas/weather-report.xsd` schema before being returned.



**Possible errors:**

- `401 Unauthorized` – Missing or invalid API key.

- `422 Unprocessable Entity` – Invalid limit or unsupported response format.

- `500 Internal Server Error` – XML validation failed.



### 3.3 Retrieve a Specific Weather Log



Retrieves one weather record using its ID.



| Property | Value |

|---|---|

| Method | GET |

| Endpoint | `/weather/logs/{id}` |

| Authentication | Required |

| Success Status | 200 OK |



**Example request:**

```http

GET /api/v1/weather/logs/1

X-API-Key: YOUR_API_KEY

```



**Example success response:**

```json

{

  "status": 200,

  "data": {

    "id": 1,

    "city": "Iligan City",

    "temperatureC": 25.2,

    "fetchedAt": "2026-11-20 10:30:00"

  }

}

```



**Possible errors:**

- `401 Unauthorized` – Missing or invalid API key.

- `404 Not Found` – No weather log exists with the specified ID.



## 4. Standard Error Response



The API uses a consistent JSON structure for error responses.



**Example:**

```json

{

  "status": 422,

  "error": {

    "code": "invalid_limit",

    "message": "limit must be 1 to 50."

  }

}

```



## 5. HTTP Status Codes



| Status Code | Description |

|---|---|

| 200 | OK – Request completed successfully. |

| 201 | Created – Weather data successfully retrieved and saved. |

| 401 | Unauthorized – Missing or invalid API key. |

| 404 | Not Found – Requested weather record does not exist. |

| 422 | Unprocessable Entity – Invalid query parameter or format. |

| 500 | Internal Server Error – XML validation failed. |

| 502 | Bad Gateway – External weather service request failed. |



## 6. Database



The API stores weather records in an SQLite database.



**Table:** `weather_logs`



| Field | Description |

|---|---|

| `id` | Unique weather record identifier. |

| `city` | City where the weather information was retrieved. |

| `temperature` | Retrieved temperature in Celsius. |

| `fetched_at` | Date and time the weather data was retrieved. |



## 7. XML Schema



The XML response is validated against:



```text

app/Schemas/weather-report.xsd

```



The schema defines the `weatherReport` root element and its `reading` elements, including the record ID, city, temperature, and retrieval timestamp.





