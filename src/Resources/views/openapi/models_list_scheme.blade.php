  /admin/api/models:
    get:
      tags:
        - Base
      summary: Receive all available models to interact with via REST API
      responses:
        '200':
          description: Success response
          content:
            application/json:
              schema:
                type: object
                properties:
                    data:
                      type: array
                      items:
                        type: object
                        properties:
                          name:
                            type: string
                          table:
                            type: string

