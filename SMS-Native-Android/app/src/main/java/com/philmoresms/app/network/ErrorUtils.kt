package com.philmoresms.app.network

import com.google.gson.Gson
import retrofit2.HttpException

/**
 * Helpers for surfacing friendly error messages from the backend API.
 * The backend returns JSON like {"status":"error","message":"..."} on failures.
 */
object ErrorUtils {

    private val gson = Gson()

    /** Extracts the backend "message" from an error (non-2xx) response, or null if it can't be parsed. */
    fun getErrorMessage(e: HttpException): String? {
        return try {
            val body = e.response()?.errorBody()?.string()
            if (body.isNullOrBlank()) {
                null
            } else {
                gson.fromJson(body, BaseResponse::class.java)?.message
            }
        } catch (ex: Exception) {
            null
        }
    }
}
