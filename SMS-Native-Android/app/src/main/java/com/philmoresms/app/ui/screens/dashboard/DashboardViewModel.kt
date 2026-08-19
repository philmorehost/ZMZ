package com.philmoresms.app.ui.screens.dashboard

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.philmoresms.app.network.BaseResponse
import com.philmoresms.app.network.ErrorUtils
import com.philmoresms.app.network.RetrofitClient
import kotlinx.coroutines.launch
import retrofit2.HttpException

class DashboardViewModel : ViewModel() {
    var data by mutableStateOf<BaseResponse?>(null)
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)

    fun fetchSummary() {
        loading = true
        error = null
        viewModelScope.launch {
            try {
                data = RetrofitClient.apiService.getSummary()
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to fetch dashboard data (HTTP ${e.code()})"
            } catch (e: Exception) {
                error = e.message ?: "An unexpected error occurred"
            } finally {
                loading = false
            }
        }
    }
}
