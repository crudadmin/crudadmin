  /admin/api/model_scheme/{table}:
    get:
      tags:
        - Base
      summary: GET OPENAPI scheme for given model
      parameters:
        - in: path
          name: table
          schema:
            type: string
          required: true
          description: Table name from models list to view all model scheme
      responses:
        '200':
          description: Success response

