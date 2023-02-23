  /models:
    get:
      tags:
        - Base
      summary: Receive all available models to interact with via REST API
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Success response
          content:
            application/json:
              schema:
                type: object
                properties:
                    model_name:
                      type: object
                      properties:
                        name:
                          type: string
                        relations:
                          type: array
                          items:
                            type: string

